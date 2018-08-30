<?php

const DEFAULT_RES_MSG = 'Response data are not sent from the merchant acquirer!';
use Wirecard\PaymentSdk\Config;
use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Config\SepaConfig;
use Wirecard\PaymentSdk\Transaction\IdealTransaction;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;
use Wirecard\PaymentSdk\Transaction\SofortTransaction;
use Wirecard\PaymentSdk\TransactionService;

/**
 * General functions which are not specific for the WPP domain.
 */

/**
 * Redirect to a provided URL.
 *
 * @param $url
 */
function redirect($url)
{
    ob_start();
    header('Location: ' . $url);
    ob_end_flush();
    exit();
}

/**
 * Returns protocol, server name and port for the current page.
 *
 * @return string Base url of the application
 *
 */
function getBaseUrl()
{
    $baseUrl = $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . '/';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
        $baseUrl = "https://" . $baseUrl;
    } else {
        $baseUrl = "http://" . $baseUrl;
    }
    return $baseUrl;
}

/**
 * Shows content of response message depending on passed over attribute.
 *
 * @param $attr
 * @param $hasToBeEncoded
 * @return string Returns either the content of the response message or a default info message.
 */
function showResponseData($attr, $hasToBeEncoded = false)
{
    if ($hasToBeEncoded) {
        return isset($_SESSION['response'][$attr]) ? base64_decode($_SESSION['response'][$attr]) : DEFAULT_RES_MSG;
    }
    return isset($_SESSION['response'][$attr]) ? $_SESSION['response'][$attr] : DEFAULT_RES_MSG;
}

/**
 * Checks whether the signature of a payment response is valid.
 *
 * @param string $responseBase64
 * @param string $signatureBase64
 * @param string $merchantSecretKey
 * @return bool
 */
function isValidSignature($responseBase64, $signatureBase64, $merchantSecretKey)
{
    $signature = hash_hmac('sha256', $responseBase64, $merchantSecretKey, $raw_output = true);
    return hash_equals($signature, base64_decode($signatureBase64));
}

/**
 * Shows message if signature is valid.
 *
 */
function showValidSignature()
{
    require_once('../config.php');

    $isResponseBase64 = isset($_SESSION['response']['response-base64']);
    $isResponseSignatureBase64 = isset($_SESSION['response']['response-signature-base64']);
    $signatureVerification = false;

    if ($isResponseBase64 && $isResponseSignatureBase64) {
        $responseBase64 = trim($_SESSION['response']['response-base64']);
        $signatureBase64 = trim($_SESSION['response']['response-signature-base64']);
        $signatureVerification = (isValidSignature($responseBase64, $signatureBase64, SECRET_KEY));
    }

    $isResponseSet = isset($_SESSION['response']['response-signature-base64']);
    if ($isResponseSet) {
        echo $signatureVerification ? 'True' : 'False';
    } else {
        echo DEFAULT_RES_MSG;
    }
}

/**
 * Creates an instance of Wirecard\PaymentSdk\TransactionService
 *
 * @param string $paymentMethod
 * @return Wirecard\PaymentSdk\TransactionService
 * Returns a Wirecard\PaymentSdk\TransactionService with a test configuration.
 */
function createTransactionService($paymentMethod)
{
    $baseUrl = 'https://api-test.wirecard.com';
    $httpUser = '';
    $httpPass = '';

    if ($paymentMethod === 'creditcard' || $paymentMethod === 'paypal') {
        $httpUser = '70000-APITEST-AP';
        $httpPass = 'qD2wzQ_hrc!8';
    } elseif ($paymentMethod === 'sepadirectdebit' || $paymentMethod === 'ideal'
        || $paymentMethod === 'sofortbanking') {
        $httpUser = '16390-testing';
        $httpPass = '3!3013=D3fD8X7';
    }

    // The configuration is stored in an object containing the connection settings set above.
    // A default currency can also be provided.
    $config = new Config\Config($baseUrl, $httpUser, $httpPass, 'EUR');

    // Set a public key for certificate pinning used for response signature validation.
    // This certificate needs to be always up to date.
    $certPath = $_SERVER['DOCUMENT_ROOT'] . '/wpp-integration-demo-php/certificate/api-test.wirecard.com.crt';
    $publicKey = file_get_contents($certPath);
    $config->setPublicKey($publicKey);

    // ## Payment methods
    // Each payment method can be configured with an individual merchant account ID and the corresponding key.
    // The configuration object for Credit Card is a little different than other payment methods and can be
    // instantiated without any parameters. If you wish to omit non-3-D transactions you can just leave out the
    // maid and secret in the default CreditCardConfig. However if you want to use non-3-D transactions you have two
    // ways of setting the credentials. First via setting the parameters maid and secret -

    $merchant_account_id_cc = '53f2895a-e4de-4e82-a813-0d87a10e55e6';
    $merchant_account_secret_key_cc = 'dbc5a498-9a66-43b9-bf1d-a618dd399684';

    // ### Credit Card Non-3-D
    $creditcardConfig = new CreditCardConfig();

    ### Credit Card Non-3-D
    $creditcardConfig->setNonThreeDCredentials($merchant_account_id_cc, $merchant_account_secret_key_cc);

    // ### Credit Card 3-D
    $creditcardConfig->setThreeDCredentials($merchant_account_id_cc, $merchant_account_secret_key_cc);

    $config->add($creditcardConfig);

    // ### PayPal
    $paypalMAID = '2a0e9351-24ed-4110-9a1b-fd0fee6bec26';
    $paypalKey = 'dbc5a498-9a66-43b9-bf1d-a618dd399684';
    $paypalConfig = new PaymentMethodConfig(PayPalTransaction::NAME, $paypalMAID, $paypalKey);
    $config->add($paypalConfig);

    // ### iDEAL
    $IdealMAID = '4aeccf39-0d47-47f6-a399-c05c1f2fc819';
    $IdealSecretKey = 'dbc5a498-9a66-43b9-bf1d-a618dd399684';
    $IdealConfig = new PaymentMethodConfig(IdealTransaction::NAME, $IdealMAID, $IdealSecretKey);
    $config->add($IdealConfig);

    // ### Sofortbanking
    $sofortMAID = '6c0e7efd-ee58-40f7-9bbd-5e7337a052cd';
    $sofortSecretKey = 'dbc5a498-9a66-43b9-bf1d-a618dd399684';
    $sofortConfig = new PaymentMethodConfig(SofortTransaction::NAME, $sofortMAID, $sofortSecretKey);
    $config->add($sofortConfig);

    // ### SEPA Direct Debit
    $sepaDirectDebitMAID = '933ad170-88f0-4c3d-a862-cff315ecfbc0';
    $sepaDirectDebitKey = 'ecdf5990-0372-47cd-a55d-037dccfe9d25';
    // SEPA requires the creditor ID, therefore a different config object is used.
    $sepaDirectDebitConfig = new SepaConfig(
        SepaDirectDebitTransaction::NAME,
        $sepaDirectDebitMAID,
        $sepaDirectDebitKey
    );
    $sepaDirectDebitConfig->setCreditorId('DE98ZZZ09999999999');
    $config->add($sepaDirectDebitConfig);

    $sepaCreditTransferMAID = '59a01668-693b-49f0-8a1f-f3c1ba025d45';
    $sepaCreditTransferKey = 'ecdf5990-0372-47cd-a55d-037dccfe9d25';
    // SEPA requires the creditor ID, therefore a different config object is used.
    $sepaCreditTransferConfig = new SepaConfig(
        SepaCreditTransferTransaction::NAME,
        $sepaCreditTransferMAID,
        $sepaCreditTransferKey
    );
    $sepaCreditTransferConfig->setCreditorId('DE98ZZZ09999999999');
    $config->add($sepaCreditTransferConfig);

    return new TransactionService($config);
}

/**
 * Echoes an output containing the code and message of the failure response.
 *
 * @param Wirecard\PaymentSdk\Response\FailureResponse $response
 */
function echoFailureResponse($response)
{
    // In our example we iterate over all errors and echo them out.
    // You should display them as error, warning or information based on the given severity.
    foreach ($response->getStatusCollection() as $status) {
        /**
         * @var $status \Wirecard\PaymentSdk\Entity\Status
         */
        $severity = ucfirst($status->getSeverity());
        $code = $status->getCode();
        $description = $status->getDescription();
        echo sprintf('%s with code %s and message "%s" occurred.<br>', $severity, $code, $description);
    }
}

/**
 * @param $baseUrl
 * @param \Wirecard\PaymentSdk\Response\SuccessResponse $response
 * @param \Wirecard\PaymentSdk\Config\Config|null $config
 * @return string
 */
function getTransactionLink($baseUrl, $response, $config = null)
{
    if ($config !== null) {
        $authorization = $config->getHttpUser() . ':' . $config->getHttpPassword();
        $baseUrl = str_replace("//", "//$authorization@", $baseUrl);
    }

    $transactionId = $response->getTransactionId();
    $output = 'Transaction ID: ';
    $output .= sprintf(
        '<a href="' . $baseUrl . '/engine/rest/merchants/%s/payments/%s">',
        $response->findElement('merchant-account-id'),
        $transactionId
    );
    $output .= $transactionId;
    $output .= '</a>';
    return $output;
}
