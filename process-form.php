<?php
header('Content-Type: application/json');

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

$name        = strip_tags($first . ' ' . $last);
$email_safe  = strip_tags($email);
$phone_safe  = strip_tags($phone);
$subj_safe   = strip_tags($subject);
$msg_safe    = strip_tags($message);

$to      = 'booking@nilora.com.au';
$subjectLine = "[{$subj_safe}] Enquiry from {$name} - NILORA Resort";

$body  = "You have received a new enquiry from the NILORA Resort website.\r\n\r\n";
$body .= "-------------------------------------------\r\n";
$body .= "Name:    {$name}\r\n";
$body .= "Email:   {$email_safe}\r\n";
$body .= "Phone:   " . ($phone_safe ?: 'Not provided') . "\r\n";
$body .= "Subject: {$subj_safe}\r\n";
$body .= "-------------------------------------------\r\n\r\n";
$body .= "Message:\r\n{$msg_safe}\r\n\r\n";
$body .= "-------------------------------------------\r\n";
$body .= "Sent from: nilora.com.au/contact\r\n";
$body .= "Time: " . date('d M Y, H:i T') . "\r\n";

$headers  = "From: noreply@nilora.com.au\r\n";
$headers .= "Reply-To: {$email_safe}\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$logFile = __DIR__ . '/contact_error.log';

$result = mail($to, $subjectLine, $body, $headers);

if ($result) {
    echo json_encode(['success' => true, 'message' => "Thank you! Your message has been sent. We'll be in touch within 24 hours."]);
} else {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " mail() returned false\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to send your message. Please email us directly at booking@nilora.com.au']);
}
