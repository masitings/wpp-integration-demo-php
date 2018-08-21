<?php
require_once('base.php');
require_once('../util/general-functions.php');

$paymentMethod = $_GET['method'];
$payload = createPayloadEmbedded($paymentMethod);
$payload['options']['frame-ancestor'] = getBaseUrl();
if (retrievePaymentRedirectUrl($payload, $paymentMethod)) {
    redirect('../payment/embedded.php');
}
