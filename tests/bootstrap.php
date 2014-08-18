<?php

require __DIR__ . '/../Tester/bootstrap.php';
require __DIR__ . '/../Tester/Runner/PhpInterpreter.php';
require __DIR__ . '/../Tester/Runner/ZendPhpInterpreter.php';
require __DIR__ . '/../Tester/Runner/HhvmPhpInterpreter.php';


date_default_timezone_set('Europe/Prague');


function test(\Closure $function)
{
	$function();
}

/**
 * @return \Tester\Runner\PhpInterpreter
 */
function createInterpreter()
{
	if (defined('HHVM_VERSION')) {
		return new Tester\Runner\HhvmPhpInterpreter(PHP_BINARY);
	} else {
		return new Tester\Runner\ZendPhpInterpreter(PHP_BINARY, '-c ' . Tester\Helpers::escapeArg(php_ini_loaded_file()));
	}
}
