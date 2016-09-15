<?php
	require_once('./app/autoload.php');
	$cpr = new CreatePaymentRequest();
	$cpr->send();
?>