<?php

/**
 * @phpversion 5.4  Requires constant PHP_BINARY available since PHP 5.4.0
 */

use Tester\Assert,
	Tester\Helpers;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/TestHandler.php';
require __DIR__ . '/../Tester/Runner/PhpInterpreter.php';
require __DIR__ . '/../Tester/Runner/ZendPhpInterpreter.php';
require __DIR__ . '/../Tester/Runner/Runner.php';


$php = new Tester\Runner\ZendPhpInterpreter(PHP_BINARY, '-c ' . Tester\Helpers::escapeArg(php_ini_loaded_file()));
$runner = new Tester\Runner\Runner($php);

Assert::with($runner, function() {
	$this->findTests(__DIR__ . '/find-tests/*.phptx');
	Assert::count(1, $this->jobs);
});
