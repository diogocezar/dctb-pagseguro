<h1>Houve uma alteração no status</h1>
<?php
	header("access-control-allow-origin: https://sandbox.pagseguro.uol.com.br");
	require_once('./app/autoload.php');
	$nl = new NotificationListener();
	$nl->main();
?>