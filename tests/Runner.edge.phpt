<?php

/**
 * @phpversion 5.4  Requires constant PHP_BINARY available since PHP 5.4.0
 */

use Tester\Assert,
	Tester\Runner\Runner;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/OutputHandler.php';
require __DIR__ . '/../Tester/Runner/TestHandler.php';
require __DIR__ . '/../Tester/Runner/PhpExecutable.php';
require __DIR__ . '/../Tester/Runner/Runner.php';


class Logger implements Tester\Runner\OutputHandler
{
	public $results = [];

	function result($testName, $result, $message)
	{
		$this->results[basename($testName)] = [$result, $message];
	}

	function begin() {}
	function end() {}
}

$php = new Tester\Runner\PhpExecutable(PHP_BINARY, '-c ' . Tester\Helpers::escapeArg(php_ini_loaded_file()));
$runner = new Tester\Runner\Runner($php);
$runner->paths[] = __DIR__ . '/edge/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->run();

$cli = PHP_SAPI === 'cli';
$bug62725 = $cli && PHP_VERSION_ID >= 50400 && PHP_VERSION_ID <= 50406;
Assert::same($bug62725 ? [Runner::PASSED, NULL] : [Runner::FAILED, 'Exited with error code 231 (expected 0)'], $logger->results['shutdown.exitCode.a.phptx']);

$bug65275 = $cli && PHP_VERSION_ID >= 50300; // bug still exists
Assert::same($bug65275 ? [Runner::FAILED, 'Exited with error code 231 (expected 0)'] : [Runner::PASSED, NULL], $logger->results['shutdown.exitCode.b.phptx']);

Assert::same([Runner::SKIPPED, 'just skipping'], $logger->results['skip.phptx']);

Assert::same($bug62725 ? Runner::PASSED : Runner::FAILED, $logger->results['shutdown.assert.phptx'][0]);
Assert::match($bug62725 ? '' : "Failed: 'b' should be%A%", Tester\Dumper::removeColors($logger->results['shutdown.assert.phptx'][1]));
