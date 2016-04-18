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
	return new PhpInterpreter(
		PHP_BINARY,
		defined('HHVM_VERSION') ? [] : ['-c', php_ini_loaded_file()]
	);
}
