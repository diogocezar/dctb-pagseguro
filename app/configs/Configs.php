<?php
	/**
	* 	Config
	* 	Class to centralize all configurations of project
	* 	Author: Diogo Cezar Teixeira Batista
	*	Year: 2016
	*/
	class Configs{
		public static $configs = array
		(
			'pagseguro' => array(
				'environment'    => 'sandbox',
				'email'          => 'diogo@diogocezar.com',
				'token'          => '9C16049D5E124FF6B818BB75B3BACBF7',
				'appId'          => 'app3191152631',
				'appKey'         => 'ABEDBD4937371A4EE4EF0F8DD668B264',
				'logs'           => './logs/logs.txt',
				'charset'        => 'UTF-8',
				'redirect'       => 'http://projects.diogocezar.com.br/dctb-pagseguro/redirect.php',
				'notification'   => 'http://projects.diogocezar.com.br/dctb-pagseguro/notification.php'
			),
		);
	}
?>