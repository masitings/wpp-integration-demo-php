<?php

require '../../../vendor/autoload.php';

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;

$tokenId = htmlspecialchars($_POST['tokenId']);
$amountNumber = htmlspecialchars($_POST['amountNumber']);
$currency = htmlspecialchars($_POST['amountCurrency']);

$amount = new Amount((float)$amountNumber, $currency);

$transaction = new CreditCardTransaction();
$transaction->setAmount($amount);
$transaction->setTokenId($tokenId);

$service = initTransactionService(CREDITCARD);

try {
    $response = $service->pay($transaction);
} catch (\Http\Client\Exception $e) {
    echo 'Transaction failed: ', $e->getMessage(), '\n';
}

if ($response instanceof SuccessResponse) {
    echo 'Successful payment.<br>';
    echo 'TransactionID: ' . $response->getTransactionId();
    require '../showButton.php';
} elseif ($response instanceof FailureResponse) {
    echoFailureResponse($response);
}
