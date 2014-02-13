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
