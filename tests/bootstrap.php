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


function createExecutable($path, array $args = NULL)
{
	return defined('HHVM_VERSION')
		? new Tester\Runner\HhvmExecutable($path, PHP_VERSION, $args)
		: new Tester\Runner\ZendPhpExecutable($path, PHP_VERSION, substr(PHP_SAPI, 0, 3) === 'cgi', $args);
}
