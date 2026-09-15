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

$name = strip_tags($first . ' ' . $last);

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.office365.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'booking@nilora.com.au';
    $mail->Password   = 'ENTER_MICROSOFT365_PASSWORD_HERE';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('booking@nilora.com.au', 'NILORA Resort Website');
    $mail->addAddress('booking@nilora.com.au', 'NILORA Booking');
    $mail->addReplyTo($email, $name);

    $mail->Subject = "[" . strip_tags($subject) . "] Enquiry from {$name} - NILORA Resort";

    $mail->Body =
        "You have received a new enquiry from the NILORA Resort website.\r\n\r\n" .
        "-------------------------------------------\r\n" .
        "Name:    {$name}\r\n" .
        "Email:   " . strip_tags($email) . "\r\n" .
        "Phone:   " . (strip_tags($phone) ?: 'Not provided') . "\r\n" .
        "Subject: " . strip_tags($subject) . "\r\n" .
        "-------------------------------------------\r\n\r\n" .
        "Message:\r\n" . strip_tags($message) . "\r\n\r\n" .
        "-------------------------------------------\r\n" .
        "Sent from: nilora.com.au/contact\r\n" .
        "Time: " . date('d M Y, H:i T') . "\r\n";

    $mail->send();
    echo json_encode(['success' => true, 'message' => "Thank you! Your message has been sent. We'll be in touch within 24 hours."]);

} catch (Exception $e) {
    $logFile = __DIR__ . '/contact_error.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " ERROR: " . $mail->ErrorInfo . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to send your message. Please email us directly at booking@nilora.com.au']);
}
