<?php

use Tester\Runner\Interpreters;


require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/Runner/PhpInterpreter.php';
require __DIR__ . '/../src/Runner/Interpreters/AbstractInterpreter.php';
require __DIR__ . '/../src/Runner/Interpreters/ZendPhpCgiInterpreter.php';
require __DIR__ . '/../src/Runner/Interpreters/ZendPhpCliInterpreter.php';
require __DIR__ . '/../src/Runner/Interpreters/ZendPhpDbgInterpreter.php';
require __DIR__ . '/../src/Runner/Interpreters/HhvmPhpInterpreter.php';


date_default_timezone_set('Europe/Prague');


function test(\Closure $function)
{
	$function();
}

/** @return Tester\Runner\PhpInterpreter */
function createInterpreter()
{
	if (defined('HHVM_VERSION')) {
		return new Interpreters\HhvmPhpInterpreter(PHP_BINARY);
	} elseif (defined('PHPDBG_VERSION')) {
		return new Interpreters\ZendPhpDbgInterpreter(PHP_BINARY, ['-c', php_ini_loaded_file()]);
	} elseif (PHP_SAPI === 'cli') {
		return new Interpreters\ZendPhpCliInterpreter(PHP_BINARY, ['-c', php_ini_loaded_file()]);
	} else {
		return new Interpreters\ZendPhpCgiInterpreter(PHP_BINARY, ['-c', php_ini_loaded_file()]);
	}
}
