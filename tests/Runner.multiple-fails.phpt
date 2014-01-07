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

$runner = new Runner(new Tester\Runner\PhpExecutable(PHP_BINARY));
$runner->paths[] = __DIR__ . '/multiple-fails/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->run();

Assert::match(
	"TestCase in file '%a%testcase-no-methods.phptx' does not contain test methods.",
	$logger->results['testcase-no-methods.phptx'][1]
);
Assert::same( Runner::SKIPPED, $logger->results['testcase-no-methods.phptx'][0] );

Assert::match(
	"Cannot list TestCase methods in file '%a%testcase-not-call-run.phptx'. Do you call TestCase::run() in it?",
	$logger->results['testcase-not-call-run.phptx'][1]
);
Assert::same( Runner::FAILED, $logger->results['testcase-not-call-run.phptx'][0] );

Assert::same( 2, count($logger->results) );
