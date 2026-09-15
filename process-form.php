<?php
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

// Honeypot check
if (!empty($_POST['website'])) {
    echo json_encode(['success' => true, 'message' => 'Thank you! Your message has been sent.']);
    exit;
}

// Validate required fields
$first   = trim($_POST['first_name'] ?? '');
$last    = trim($_POST['last_name']  ?? '');
$email   = trim($_POST['email']      ?? '');
$phone   = trim($_POST['phone']      ?? '');
$subject = trim($_POST['subject']    ?? 'General Enquiry');
$message = trim($_POST['message']    ?? '');

if (!$first || !$email || !$message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$name = htmlspecialchars($first . ' ' . $last);

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'mail.nilora.com.au';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'booking@nilora.com.au';
    $mail->Password   = 'ENTER_PASSWORD_HERE';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom('booking@nilora.com.au', 'NILORA Resort Website');
    $mail->addAddress('booking@nilora.com.au', 'NILORA Booking');
    $mail->addReplyTo($email, $name);

    $mail->Subject = "[{$subject}] Enquiry from {$name} – NILORA Resort";

    $mail->Body =
        "You have received a new enquiry from the NILORA Resort website.\n\n" .
        "-------------------------------------------\n" .
        "Name:    {$name}\n" .
        "Email:   {$email}\n" .
        "Phone:   " . (htmlspecialchars($phone) ?: 'Not provided') . "\n" .
        "Subject: " . htmlspecialchars($subject) . "\n" .
        "-------------------------------------------\n\n" .
        "Message:\n" . htmlspecialchars($message) . "\n\n" .
        "-------------------------------------------\n" .
        "Sent from: nilora.com.au/contact\n" .
        "Time: " . date('d M Y, H:i T') . "\n";

    $mail->send();
    echo json_encode(['success' => true, 'message' => "Thank you! Your message has been sent. We'll be in touch within 24 hours."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to send your message. Please email us directly at booking@nilora.com.au']);
}
