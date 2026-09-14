<?php

require_once __DIR__ . '/autoload.php';

use OnlinePayments\Sdk\CommunicatorConfiguration;
use OnlinePayments\Sdk\Authentication\V1HmacAuthenticator;
use OnlinePayments\Sdk\Communicator;
use OnlinePayments\Sdk\Client;

use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use OnlinePayments\Sdk\Domain\Order;
use OnlinePayments\Sdk\Domain\AmountOfMoney;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputBase;
//test only
use OnlinePayments\Sdk\Domain\Customer;
use OnlinePayments\Sdk\Domain\ContactDetails;

$config = require __DIR__ . '/config.php';

// set amount and email variables
$amount = $_GET['amount'] ?? 0;

if (!isset($_GET['email']) || trim($_GET['email']) === '') {
    http_response_code(400);
    die('Email is required.');
}

$email = trim($_GET['email']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    die('Invalid email address.');
}

if (!isset($_GET['amount']) || trim($_GET['amount']) === '') {
    http_response_code(400);
    die('Amount is required.');
}

$amount = filter_var($_GET['amount'], FILTER_VALIDATE_FLOAT);

if ($amount === false || $amount <= 0) {
    http_response_code(400);
    die('Invalid amount.');
}
// end setting of amount and email variables
$merchantId  = $config['merchant_id'];
$apiKey      = $config['api_key'];
$apiSecret   = $config['api_secret'];
$apiEndpoint = $config['api_endpoint'];
$integrator  = $config['integrator'];
$returnUrl = 'https://nilora.com.au/onlinepayment/payment-return.php';

$communicatorConfiguration = new CommunicatorConfiguration($apiKey,$apiSecret,$apiEndpoint,$integrator);
$authenticator = new V1HmacAuthenticator($communicatorConfiguration);
$communicator = new Communicator($communicatorConfiguration,$authenticator);

$client = new Client($communicator);
$merchantClient = $client->merchant($merchantId);
$hostedCheckoutClient = $merchantClient->hostedCheckout();
$createHostedCheckoutRequest = new CreateHostedCheckoutRequest();

/* Authorize and capture */
$cardPaymentMethodSpecificInput = new CardPaymentMethodSpecificInputBase();
$cardPaymentMethodSpecificInput -> setAuthorizationMode('SALE');
$createHostedCheckoutRequest -> setCardPaymentMethodSpecificInput($cardPaymentMethodSpecificInput);

/* Order */
$order = new Order();

$amountOfMoney = new AmountOfMoney();
$amountOfMoney->setCurrencyCode('AUD');

// Convert AUD dollars to cents
$amountInCents = round((float)$amount * 100);
$amountOfMoney -> setAmount($amountInCents);
$order -> setAmountOfMoney($amountOfMoney);
//recording email
$customer = new Customer();

$contactDetails = new ContactDetails();
$contactDetails->setEmailAddress($email);
$customer->setContactDetails($contactDetails);

$customer->setMerchantCustomerId($email);

$order->setCustomer($customer);
//end test email

$createHostedCheckoutRequest->setOrder($order);

/* Return / Post-Payment URL */
$hostedCheckoutSpecificInput = new HostedCheckoutSpecificInput();
$hostedCheckoutSpecificInput->setReturnUrl($returnUrl);
$createHostedCheckoutRequest->setHostedCheckoutSpecificInput($hostedCheckoutSpecificInput);

/*Create Hosted Checkout */
try 
{
    $response = $hostedCheckoutClient->createHostedCheckout($createHostedCheckoutRequest);
    $hostedCheckoutId = $response->getHostedCheckoutId();
    $returnMac = $response->getRETURNMAC();
    $redirectUrl = $response->getRedirectUrl();
    /* Fallback for SDK versions that return partialRedirectUrl */
    if (!$redirectUrl) 
    {
        $partialRedirectUrl = $response->getPartialRedirectUrl();
        if ($partialRedirectUrl) 
        {
            $redirectUrl = 'https://payment.' . $partialRedirectUrl;
        }
    }

    if (!$redirectUrl) 
    {
         $logFile = __DIR__ . '/logs/payment_error.log';

        // Create logs folder if it doesn't exist
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0777, true);
        }

        // Capture the response/error
        $errorDetails = print_r($response, true);

        $logMessage =
            "Payment Error: " . date('Y-m-d H:i:s') . "\n" .
            $errorDetails . "\n\n";

        file_put_contents(
            $logFile,
            $logMessage,
            FILE_APPEND
        );

        echo '<h2>Payment Error</h2>';
        echo '<p>Unable to obtain Checkout redirect URL. Please contact support for assistance.</p>';
        exit;
    }

    /*  Redirect customer */
    header('Location: ' . $redirectUrl);
    exit;
} 
catch (\Throwable $e) 
{
    $logFile = __DIR__ . '/logs/payment_error.log';

    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }

    $logMessage = 
        date('Y-m-d H:i:s') . "\n" .
        "Error: " . $e->getMessage() . "\n" .
        "File: " . $e->getFile() . "\n" .
        "Line: " . $e->getLine() . "\n\n";

    file_put_contents($logFile,$logMessage,FILE_APPEND | LOCK_EX);

    http_response_code(500);

    echo '<h2>Payment Error</h2>';
    echo '<p>We were unable to create the payment. Please contact support for assistance.</p>';

    exit;
}