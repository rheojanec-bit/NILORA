<?php
header('Content-Type: application/json');

$to = 'booking@nilora.com.au';

// Honeypot check — bots fill this, humans don't
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

$name        = htmlspecialchars($first . ' ' . $last);
$email_safe  = htmlspecialchars($email);
$phone_safe  = htmlspecialchars($phone);
$subj_safe   = htmlspecialchars($subject);
$msg_safe    = htmlspecialchars($message);

$mail_subject = "[$subj_safe] Enquiry from $name – NILORA Resort";

$body  = "You have received a new enquiry from the NILORA Resort website.\n\n";
$body .= "-------------------------------------------\n";
$body .= "Name:    $name\n";
$body .= "Email:   $email_safe\n";
$body .= "Phone:   " . ($phone_safe ?: 'Not provided') . "\n";
$body .= "Subject: $subj_safe\n";
$body .= "-------------------------------------------\n\n";
$body .= "Message:\n$msg_safe\n\n";
$body .= "-------------------------------------------\n";
$body .= "Sent from: nilora.com.au/contact\n";
$body .= "Time: " . date('d M Y, H:i T') . "\n";

$headers  = "From: NILORA Website <noreply@nilora.com.au>\r\n";
$headers .= "Reply-To: $email_safe\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

if (mail($to, $mail_subject, $body, $headers)) {
    echo json_encode(['success' => true, 'message' => 'Thank you! Your message has been sent. We\'ll be in touch within 24 hours.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to send your message. Please email us directly at booking@nilora.com.au']);
}
