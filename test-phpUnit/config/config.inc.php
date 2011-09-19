<?php
	error_reporting(E_ALL ^ E_NOTICE);
	define('_XE_PATH_', str_replace('test-phpUnit/config/config.inc.php', '', str_replace('\\', '/', __FILE__)));
	define('_TEST_PATH_', _XE_PATH_ . 'test-phpUnit/');

	if(!defined('__DEBUG__')) define('__DEBUG__', 4);
        define('__ZBXE__', true);

	require_once(_XE_PATH_.'config/config.inc.php');

	//require_once(_XE_PATH_.'classes/db/DB.class.php');
	//require_once(_XE_PATH_.'classes/db/DBCubrid.class.php');
	//require_once(_XE_PATH_.'classes/db/DBMssql.class.php');

?>