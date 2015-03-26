<?php

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/Runner/PhpInterpreter.php';
require __DIR__ . '/../src/Runner/ZendPhpInterpreter.php';
require __DIR__ . '/../src/Runner/HhvmPhpInterpreter.php';


date_default_timezone_set('Europe/Prague');


function test(\Closure $function)
{
	$function();
}

/** @return Tester\Runner\PhpInterpreter */
function createInterpreter()
{
	return defined('HHVM_VERSION')
		? new Tester\Runner\HhvmPhpInterpreter(PHP_BINARY)
		: new Tester\Runner\ZendPhpInterpreter(PHP_BINARY, ' -c ' . Tester\Helpers::escapeArg(php_ini_loaded_file()));
}
