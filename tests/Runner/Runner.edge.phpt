<?php

use Tester\Assert;
use Tester\Runner\Test;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';


class Logger implements Tester\Runner\OutputHandler
{
	public $results = [];


	public function result($testName, $result, $message)
	{
		$this->results[basename($testName)] = [$result, $message];
	}


	public function begin()
	{
	}


	public function end()
	{
	}
}

$runner = new Tester\Runner\Runner(createInterpreter());
$runner->paths[] = __DIR__ . '/edge/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->run();

$cli = PHP_SAPI === 'cli';
$bug62725 = $cli && PHP_VERSION_ID <= 50406;
Assert::same($bug62725 ? [Test::PASSED, null] : [Test::FAILED, 'Exited with error code 231 (expected 0)'], $logger->results['shutdown.exitCode.a.phptx']);

$bug65275 = $cli;
Assert::same($bug65275 ? [Test::FAILED, 'Exited with error code 231 (expected 0)'] : [Test::PASSED, null], $logger->results['shutdown.exitCode.b.phptx']);

Assert::same([Test::SKIPPED, 'just skipping'], $logger->results['skip.phptx']);

Assert::same($bug62725 ? Test::PASSED : Test::FAILED, $logger->results['shutdown.assert.phptx'][0]);
Assert::match($bug62725 ? '' : "Failed: 'b' should be%A%", Tester\Dumper::removeColors($logger->results['shutdown.assert.phptx'][1]));
