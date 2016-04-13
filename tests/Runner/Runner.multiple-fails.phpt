<?php

use Tester\Assert;
use Tester\Dumper;
use Tester\Runner\Runner;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';


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

$interpreter = createInterpreter();
$interpreter->addPhpIniOption('display_errors', 'on');
$interpreter->addPhpIniOption('html_errors', 'off');

$runner = new Runner($interpreter);
$runner->paths[] = __DIR__ . '/multiple-fails/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->run();

Assert::match(
	"TestCase in file '%a%testcase-no-methods.phptx' does not contain test methods.",
	$logger->results['testcase-no-methods.phptx'][1]
);
Assert::same(Runner::SKIPPED, $logger->results['testcase-no-methods.phptx'][0]);


$bug62725 = PHP_SAPI === 'cli' && PHP_VERSION_ID <= 50406;
$issue162 = defined('HHVM_VERSION') && HHVM_VERSION_ID < 30400;
Assert::match(
	$bug62725 || $issue162
		? "Cannot list TestCase methods in file '%a%testcase-not-call-run.phptx'. Do you call TestCase::run() in it?"
		: 'Error: This test forgets to execute an assertion.',
	trim($logger->results['testcase-not-call-run.phptx'][1])
);
Assert::same(Runner::FAILED, $logger->results['testcase-not-call-run.phptx'][0]);


Assert::match(
	"Skipped:\npre-skip",
	trim($logger->results['testcase-pre-skip.phptx'][1])
);
Assert::same(Runner::SKIPPED, $logger->results['testcase-pre-skip.phptx'][0]);


Assert::match(
	"Failed: pre-fail\n%A%",
	trim(Dumper::removeColors($logger->results['testcase-pre-fail.phptx'][1]))
);
Assert::same(Runner::FAILED, $logger->results['testcase-pre-fail.phptx'][0]);


Assert::match(
	defined('HHVM_VERSION')
		? 'Fatal error: syntax error, unexpected $end in %a%testcase-syntax-error.phptx on line %d%'
		: (defined('PHPDBG_VERSION')
			? '%A%Parse error: syntax error, unexpected end of file in %a%testcase-syntax-error.phptx on line %d%'
			: 'Parse error: syntax error, unexpected end of file in %a%testcase-syntax-error.phptx on line %d%'
		),
	trim($logger->results['testcase-syntax-error.phptx'][1])
);
Assert::same(Runner::FAILED, $logger->results['testcase-syntax-error.phptx'][0]);


Assert::same(5, count($logger->results));
