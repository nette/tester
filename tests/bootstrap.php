<?php declare(strict_types=1);

use Tester\Runner\PhpInterpreter;


require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/Runner/PhpInterpreter.php';


Tester\Environment::setupFunctions();


function createInterpreter(): PhpInterpreter
{
	$args = [];
	if ($file = php_ini_loaded_file()) {
		array_push($args, '-n', '-c', $file);
	}

	return new PhpInterpreter(PHP_BINARY, $args);
}
