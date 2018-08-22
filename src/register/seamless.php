<?php
require_once('../config.php');
require_once('base.php');
require_once('../util/helperFunctions.php');

$creditcard = CCARD;
$payload = createPayloadStandalone($creditcard);
$payload['options']['frame-ancestor'] = getBaseUrl();
$payload['options']['mode'] = 'seamless';
if (retrievePaymentRedirectUrl($payload, $creditcard)) {
    redirect('../payment/seamless.php');
}
