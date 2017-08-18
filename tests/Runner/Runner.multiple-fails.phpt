<?php

use Tester\Assert;
use Tester\Dumper;
use Tester\Runner\Runner;
use Tester\Runner\Test;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';


class Logger implements Tester\Runner\OutputHandler
{
	public $results = [];


	public function prepare(Test $test)
	{
	}


	public function finish(Test $test)
	{
		$this->results[basename($test->getFile())] = [$test->getResult(), $test->message];
	}


	public function begin()
	{
	}


	public function end()
	{
	}
}

$interpreter = createInterpreter()
	->withPhpIniOption('display_errors', 'on')
	->withPhpIniOption('html_errors', 'off');

$runner = new Runner($interpreter);
$runner->paths[] = __DIR__ . '/multiple-fails/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->run();

Assert::match(
	"TestCase in file '%a%testcase-no-methods.phptx' does not contain test methods.",
	$logger->results['testcase-no-methods.phptx'][1]
);
Assert::same(Test::SKIPPED, $logger->results['testcase-no-methods.phptx'][0]);


Assert::match(
	'Error: This test forgets to execute an assertion.',
	trim($logger->results['testcase-not-call-run.phptx'][1])
);
Assert::same(Test::FAILED, $logger->results['testcase-not-call-run.phptx'][0]);


Assert::match(
	"Skipped:\npre-skip",
	trim($logger->results['testcase-pre-skip.phptx'][1])
);
Assert::same(Test::SKIPPED, $logger->results['testcase-pre-skip.phptx'][0]);


Assert::match(
	"Failed: pre-fail\n%A%",
	trim(Dumper::removeColors($logger->results['testcase-pre-fail.phptx'][1]))
);
Assert::same(Test::FAILED, $logger->results['testcase-pre-fail.phptx'][0]);


Assert::match(
	defined('PHPDBG_VERSION')
		? '%A%Parse error: syntax error, unexpected end of file in %a%testcase-syntax-error.phptx on line %d%'
		: 'Parse error: syntax error, unexpected end of file in %a%testcase-syntax-error.phptx on line %d%',
	trim($logger->results['testcase-syntax-error.phptx'][1])
);
Assert::same(Test::FAILED, $logger->results['testcase-syntax-error.phptx'][0]);


Assert::same(5, count($logger->results));
