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
				'email'          => 'ConfigsMy.php',
				'token'          => '',
				'appId'          => '',
				'appKey'         => '',
				'logs'           => './logs/logs.txt',
				'charset'        => 'UTF-8',
				'redirect'       => 'http://projects.diogocezar.com.br/dctb-pagseguro/redirect.php',
				'notification'   => 'http://projects.diogocezar.com.br/dctb-pagseguro/notification.php'
			),
		);
	}
?>