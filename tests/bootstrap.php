<?php

use Tester\Runner\PhpInterpreter;


require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/Runner/PhpInterpreter.php';


date_default_timezone_set('Europe/Prague');


function test(\Closure $function)
{
	$function();
}


/** @return PhpInterpreter */
function createInterpreter()
{
	$args = [];
	if (defined('HHVM_VERSION') || !strlen(php_ini_scanned_files())) {
		$args[] = '-n';
	}

	if (php_ini_loaded_file()) {
		array_push($args, '-c', php_ini_loaded_file());
	}

	return new PhpInterpreter(PHP_BINARY, $args);
}
