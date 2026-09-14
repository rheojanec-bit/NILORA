<?php

require_once __DIR__ . '/autoload.php';

use OnlinePayments\Sdk\CommunicatorConfiguration;
use OnlinePayments\Sdk\Authentication\V1HmacAuthenticator;
use OnlinePayments\Sdk\Communicator;
use OnlinePayments\Sdk\Client;

// LOAD CONFIGURATION

$config = require __DIR__ . '/config.php';

$merchantId  = $config['merchant_id'];
$apiKey      = $config['api_key'];
$apiSecret   = $config['api_secret'];
$apiEndpoint = $config['api_endpoint'];
$integrator  = $config['integrator'];

// GET HOSTED CHECKOUT ID + REFERENCE
$hostedCheckoutId = $_GET['hostedCheckoutId'] ?? '';
$returnMac        = $_GET['RETURNMAC'] ?? '';

// Merchant reference passed from payment.php
$merchantReference = $_GET['reference'] ?? 'N/A';

if ($hostedCheckoutId === '') {
    die('Missing hostedCheckoutId.');
}

//  INITIALIZE SDK
$configuration = new CommunicatorConfiguration($apiKey,$apiSecret,$apiEndpoint,$integrator);
$authenticator = new V1HmacAuthenticator($configuration);
$communicator = new Communicator($configuration,$authenticator);
$client = new Client($communicator);
$merchantClient = $client->merchant($merchantId);


// SET DEFAULT VALUES
$title        = 'Payment Status';
$message      = 'We are checking your payment status.';
$resultClass  = 'processing';
$status        = 'N/A';
$displayAmount = '0.00';
$currency      = 'AUD';
$cardNumber    = '';
$paymentId     = 'N/A';
$errorMessage  = '';

// GET PAYMENT
try 
{
    // Get Hosted Checkout
    $hostedCheckoutClient = $merchantClient->hostedCheckout();
    $checkout = $hostedCheckoutClient->getHostedCheckout($hostedCheckoutId);

    // Get payment information
    $createdPaymentOutput = $checkout->getCreatedPaymentOutput();

    if (!$createdPaymentOutput) 
    {
        throw new Exception('ANZ did not return payment information.');
    }

    $payment = $createdPaymentOutput->getPayment();

    if (!$payment) 
    {
        throw new Exception('ANZ did not return a payment.');
    }
    $paymentOutput = $payment->getPaymentOutput();
    $merchantReference = 'N/A';

    if ($paymentOutput) 
    {
        $references = $paymentOutput->getReferences();
        if ($references) 
        {
            $merchantReference = $references->getMerchantReference();
        }
    }
    // Payment ID
    $paymentId = $payment->getId();
    // Payment status
    $status = $payment->getStatus();
    // Payment output
    $paymentOutput = $payment->getPaymentOutput();
    // Amount
    $amount = 0;
    $currency = 'AUD';

    if ($paymentOutput) {

        $amountOfMoney = $paymentOutput->getAmountOfMoney();
        if ($amountOfMoney) 
        {
            $amount = $amountOfMoney->getAmount();
            $currency = $amountOfMoney->getCurrencyCode();

        }
    }
    // Convert cents to dollars
    $displayAmount = number_format($amount / 100, 2);

    // Card
    if ($paymentOutput) 
    {
        $cardOutput = $paymentOutput -> getCardPaymentMethodSpecificOutput();

        if ($cardOutput) 
        {
            $card = $cardOutput->getCard();

            if ($card) 
            {
                $cardNumber = $card->getCardNumber();
            }
        }
    }

    // GET RESULT
    if ($status === 'CAPTURED') 
    {
        $title = 'Payment Successful';
        $message = 'Your payment has been successfully processed.';
        $resultClass = 'success';
    } 
    elseif ( $status === 'PENDING_CAPTURE' || $status === 'CAPTURE_REQUESTED')
    {
        $title = 'Payment Processing';
        $message = 'Your payment has been authorized and is still being processed.';
        $resultClass ='processing';
    } 
    elseif ($status === 'REJECTED'
        || $status === 'REJECTED_CAPTURE'
        || $status === 'CANCELLED')
    {
        $title = 'Payment Failed';
        $message = 'Your payment could not be completed.';
        $resultClass ='failed';
    } 
    else 
    {
        $title ='Payment Status';
        $message = 'Your payment status is: ' . $status;
        $resultClass = 'processing';
    }
} 
catch (\Exception $e) 
{

    $title = 'Payment Error';
    $message = 'We were unable to retrieve your payment information.';
    $resultClass = 'failed';
    $errorMessage = $e->getMessage();
}
?>
<?php
// Payment Result Page
// Contact email
$contactEmail = "bookings@nilora.com";

// Optional variables do not generate PHP notices
$title = $title ?? "Payment Result";
$resultClass = $resultClass ?? "processing";
$message = $message ?? "";
$currency = $currency ?? "";
$displayAmount = $displayAmount ?? "";
$merchantReference = $merchantReference ?? "N/A";
$status = $status ?? "UNKNOWN";
$cardNumber = $cardNumber ?? "";
$errorMessage = $errorMessage ?? "";
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title)?></title>
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' fill='%230C202D'/%3E%3Ccircle cx='16' cy='16' r='8' fill='none' stroke='%23C89F35' stroke-width='2'/%3E%3Ccircle cx='16' cy='16' r='3' fill='%23C89F35'/%3E%3C/svg%3E" />
        <!-- Payment Page CSS -->
        <link rel="stylesheet" href="../assets/css/payment-return.css">
    </head>
    <body>
        <div class="page-overlay">
            <div class="container">
                <div class="brand">
                    <img src="../image-62.png" alt="Nilora Resorts & Hotels" >
                </div>
                <br>
                <br>
                <!-- Main Title -->
                <h1 class="<?= htmlspecialchars($resultClass) ?>">
                    <?= htmlspecialchars($title) ?>
                </h1>
                <!-- Message -->
                <div class="message">
                    <?= htmlspecialchars($message) ?>
                </div>
                <br>
                <!-- Payment Details -->
                <div class="details">
                    <div class="row">
                        <span class="label">Amount</span>
                        <span class="value amount-value">
                            <?= htmlspecialchars($currency) ?>
                            <?= htmlspecialchars($displayAmount) ?>
                        </span>
                    </div>
                    <!-- Reference No.-->
                    <div class="row">
                        <span class="label">Reference No.</span>
                        <span class="value reference-value" id="referenceNumber"><?= htmlspecialchars($merchantReference) ?></span>
                    </div>
                    <!-- Card No.-->
                    <?php if (!empty($cardNumber)): ?>
                        <div class="row">
                            <span class="label">Card</span>
                            <span class="value"><?= htmlspecialchars($cardNumber) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Copy Reference No.-->
                <div class="copy-wrapper">
                    <button type="button" class="copy-button" id="copyReference">
                        <span class="copy-icon">▣</span>Copy Reference No.
                    </button>
                </div>
                <!-- Error -->
                <?php if (!empty($errorMessage)): ?>
                    <div class="error">
                        <?= htmlspecialchars($errorMessage) ?>
                    </div>
                <?php endif; ?>
                <!-- Contact Information -->
                <div class="contact-box">
                    <div class="contact-icon">
                        ✉
                    </div>
                    <div class="contact-content">
                        <div class="contact-title">
                            Booking Confirmation Required
                        </div>
                        <div class="contact-text">
                            Thank you for your payment. Please email <strong><?= htmlspecialchars($contactEmail)?></strong> with your reference number so we can verify your payment and process your booking.
                        </div>
                    </div>
                </div>
                <!-- Action Buttons -->
                <div class="actions">
                    <a href="../index.html" class="button home-button">
                        Return to Home
                    </a>
                </div>
                <!-- Footer -->
                <div class="footer">
                    <div class="footer-line"></div>
                    <div class="thank-you">
                        Thank you for choosing Nilora Resorts & Hotels.
                    </div>
                    <div class="footer-text">
                        We look forward to welcoming you.
                    </div>
                </div>
            </div>
        </div>
        <script src="../assets/js/payment-return.js"></script>
    </body>
</html>