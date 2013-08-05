<?php

use Tester\Assert,
	Tester\Runner\Runner;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/OutputHandler.php';
require __DIR__ . '/../Tester/Runner/TestHandler.php';
require __DIR__ . '/../Tester/Runner/PhpExecutable.php';
require __DIR__ . '/../Tester/Runner/Runner.php';

if (PHP_VERSION_ID < 50400) {
	Tester\Environment::skip('Requires constant PHP_BINARY available since PHP 5.4.0');
}


class Logger implements Tester\Runner\OutputHandler
{
	public $results = array();

	function result($testName, $result, $message)
	{
		$this->results[basename($testName)] = array($result, $message);
	}

	function begin() {}
	function end() {}
}

$runner = new Tester\Runner\Runner(new Tester\Runner\PhpExecutable(PHP_BINARY));
$runner->paths[] = __DIR__ . '/edge/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->run();

$cli = PHP_SAPI === 'cli';
$bug62725 = $cli && PHP_VERSION_ID >= 50400 && PHP_VERSION_ID <= 50406;
Assert::same($bug62725 ? array(Runner::PASSED, NULL) : array(Runner::FAILED, 'Exited with error code 231 (expected 0)'), $logger->results['shutdown.exitCode.a.phptx']);

$bug65275 = $cli && PHP_VERSION_ID >= 50300; // bug still exists
Assert::same($bug65275 ? array(Runner::FAILED, 'Exited with error code 231 (expected 0)') : array(Runner::PASSED, NULL), $logger->results['shutdown.exitCode.b.phptx']);

Assert::same(array(Runner::SKIPPED, 'just skipping'), $logger->results['skip.phptx']);

Assert::same($bug62725 ? Runner::PASSED : Runner::FAILED, $logger->results['shutdown.assert.phptx'][0]);
Assert::match($bug62725 ? '' : "Failed: 'b' should be%A%", Tester\Dumper::removeColors($logger->results['shutdown.assert.phptx'][1]));
