<?php
/**
 * Nilora Resort — Contact Form Handler
 * Sends enquiries to booking@nilora.com.au via Microsoft 365 SMTP (STARTTLS).
 *
 * Requires: PHPMailer/src/ in the same directory as this file.
 * PHP 7.4+
 */

declare(strict_types=1);

// ─── SMTP CREDENTIALS ────────────────────────────────────────────────────────
// Replace the two placeholder values below with your real credentials.
// See README-email-setup.md for instructions on creating an app password.
const SMTP_USERNAME = 'booking@nilora.com.au';          // Your M365 email address
const SMTP_PASSWORD = 'YOUR_APP_PASSWORD_HERE';         // Paste your app password here
// ─────────────────────────────────────────────────────────────────────────────

const SMTP_HOST     = 'smtp.office365.com';
const SMTP_PORT     = 587;
const MAIL_TO       = 'booking@nilora.com.au';
const MAIL_TO_NAME  = 'Nilora Resort';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// ─── HONEYPOT CHECK ──────────────────────────────────────────────────────────
// If the hidden "website" field is filled, it's almost certainly a bot.
// Return a fake success so bots don't know they were blocked.
if (!empty($_POST['website'])) {
    echo json_encode(['success' => true, 'message' => 'Thank you for your message!']);
    exit;
}

// ─── SANITISE & VALIDATE ─────────────────────────────────────────────────────
$first_name = trim(strip_tags($_POST['first_name'] ?? ''));
$last_name  = trim(strip_tags($_POST['last_name']  ?? ''));
$email      = trim($_POST['email']   ?? '');
$phone      = trim(strip_tags($_POST['phone']   ?? ''));
$subject    = trim(strip_tags($_POST['subject']  ?? 'General Enquiry'));
$message    = trim(strip_tags($_POST['message']  ?? ''));

$errors = [];

if ($first_name === '') {
    $errors[] = 'First name is required.';
}
if ($last_name === '') {
    $errors[] = 'Last name is required.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}
if ($message === '') {
    $errors[] = 'Message is required.';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ─── LOAD PHPMAILER ──────────────────────────────────────────────────────────
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ─── SEND EMAIL ──────────────────────────────────────────────────────────────
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->Port       = SMTP_PORT;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;

    // Sender / recipients
    $mail->setFrom(SMTP_USERNAME, MAIL_TO_NAME);
    $mail->addAddress(MAIL_TO, MAIL_TO_NAME);
    $mail->addReplyTo($email, "{$first_name} {$last_name}");

    // Content
    $mail->isHTML(true);
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->Subject = "[Nilora Enquiry] {$subject} — {$first_name} {$last_name}";

    $phone_line = $phone !== '' ? "<tr><td><strong>Phone:</strong></td><td>" . htmlspecialchars($phone) . "</td></tr>" : '';

    $mail->Body = <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head><meta charset="UTF-8"><title>Nilora Enquiry</title></head>
    <body style="font-family:Arial,sans-serif;color:#0C202D;background:#f4f4f4;margin:0;padding:20px">
      <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:4px;overflow:hidden">
        <tr>
          <td style="background:#0C202D;padding:24px 32px">
            <p style="font-family:Georgia,serif;font-size:22px;color:#C89F35;margin:0;letter-spacing:0.05em">NILORA RESORT</p>
            <p style="font-size:12px;color:rgba(255,255,255,0.6);margin:4px 0 0;letter-spacing:0.15em;text-transform:uppercase">New Website Enquiry</p>
          </td>
        </tr>
        <tr>
          <td style="padding:32px">
            <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:14px">
              <tr><td style="width:120px"><strong>Name:</strong></td><td>{$first_name} {$last_name}</td></tr>
              <tr><td><strong>Email:</strong></td><td><a href="mailto:{$email}" style="color:#006FA6">{$email}</a></td></tr>
              {$phone_line}
              <tr><td><strong>Subject:</strong></td><td>{$subject}</td></tr>
            </table>
            <hr style="border:none;border-top:1px solid #e0d9cc;margin:20px 0">
            <p style="font-size:14px;margin:0 0 8px"><strong>Message:</strong></p>
            <p style="font-size:14px;line-height:1.7;white-space:pre-wrap;margin:0">{$message}</p>
          </td>
        </tr>
        <tr>
          <td style="background:#f3efe7;padding:16px 32px;font-size:11px;color:#7a8a90;text-align:center">
            Sent from the contact form at nilora.com.au · Reply directly to this email to reach {$first_name}.
          </td>
        </tr>
      </table>
    </body>
    </html>
    HTML;

    // Plain-text fallback
    $mail->AltBody = "New enquiry from the Nilora Resort website.\n\n"
        . "Name:    {$first_name} {$last_name}\n"
        . "Email:   {$email}\n"
        . ($phone !== '' ? "Phone:   {$phone}\n" : '')
        . "Subject: {$subject}\n\n"
        . "Message:\n{$message}";

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your message has been sent. We\'ll be in touch within 24 hours.',
    ]);

} catch (Exception $e) {
    // Log internally — never expose SMTP details to the client
    error_log('Nilora contact form mailer error: ' . $mail->ErrorInfo);

    echo json_encode([
        'success' => false,
        'message' => 'Sorry, your message could not be sent at this time. Please email us directly at booking@nilora.com.au or call us.',
    ]);
}
