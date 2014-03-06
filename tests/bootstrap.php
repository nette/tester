<?php

require __DIR__ . '/../Tester/bootstrap.php';


if (extension_loaded('xdebug')) {
	Tester\CodeCoverage\Collector::start(__DIR__ . '/coverage.dat');
}

date_default_timezone_set('Europe/Prague');


function test(\Closure $function)
{
	$function();
}


/**
 * @return Tester\Runner\PhpExecutable
 */
function createPhp()
{
	if (PHP_VERSION_ID < 50400) {
		Tester\Environment::skip('Requires constant PHP_BINARY available since PHP 5.4.0');
	}

	$args = '-n';
	if (($ini = php_ini_loaded_file()) !== FALSE) {
		$args .= ' -c ' . Tester\Helpers::escapeArg($ini);
	}

	return new Tester\Runner\PhpExecutable(PHP_BINARY, $args);
}
