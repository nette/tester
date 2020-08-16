<?php

declare(strict_types=1);

use Tester\Runner\PhpInterpreter;


require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/Runner/PhpInterpreter.php';


function test(string $description, Closure $function): void
{
	$function();
}


function createInterpreter(): PhpInterpreter
{
	$args = strlen((string) php_ini_scanned_files())
		? []
		: ['-n'];

	if (php_ini_loaded_file()) {
		array_push($args, '-c', php_ini_loaded_file());
	}

	return new PhpInterpreter(PHP_BINARY, $args);
}
