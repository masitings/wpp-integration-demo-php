<?php
require_once('../util/helperFunctions.php');

session_start();
$_SESSION['msg'] = 'The payment has been cancelled.';
$_SESSION['response'] = $_POST;

redirect('show.php');
