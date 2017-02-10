<?php

use Tester\Assert;
use Tester\Runner\Runner;
use Tester\Runner\TestInstance;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/TestInstance.php';
require __DIR__ . '/../../src/Runner/Runner.php';


class Logger implements Tester\Runner\OutputHandler
{
	public $results = [];

	function result(TestInstance $testInstance)
	{
		$this->results[basename($testInstance->getTestName())] = [$testInstance->getResult(), $testInstance->getMessage()];
	}

	function begin(array $testInstances) {}
	function end() {}
}

$runner = new Tester\Runner\Runner(createInterpreter());
$runner->paths[] = __DIR__ . '/edge/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->run();

$cli = PHP_SAPI === 'cli';
$bug62725 = $cli && PHP_VERSION_ID <= 50406;
Assert::same($bug62725 ? [Runner::PASSED, NULL] : [Runner::FAILED, 'Exited with error code 231 (expected 0)'], $logger->results['shutdown.exitCode.a.phptx']);

$bug65275 = !defined('HHVM_VERSION') && $cli;
Assert::same($bug65275 ? [Runner::FAILED, 'Exited with error code 231 (expected 0)'] : [Runner::PASSED, NULL], $logger->results['shutdown.exitCode.b.phptx']);

Assert::same([Runner::SKIPPED, 'just skipping'], $logger->results['skip.phptx']);

Assert::same($bug62725 ? Runner::PASSED : Runner::FAILED, $logger->results['shutdown.assert.phptx'][0]);
Assert::match($bug62725 ? '' : "Failed: 'b' should be%A%", Tester\Dumper::removeColors($logger->results['shutdown.assert.phptx'][1]));
