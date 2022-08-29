<?php

declare(strict_types=1);

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


	public function prepare(Test $test): void
	{
	}


	public function finish(Test $test): void
	{
		$this->results[basename($test->getFile())] = [$test->getResult(), $test->message];
	}


	public function begin(): void
	{
	}


	public function end(): void
	{
	}
}

$runner = new Tester\Runner\Runner(createInterpreter());
$runner->paths[] = __DIR__ . '/edge/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->run();

Assert::same([Test::FAILED, 'Exited with error code 231 (expected 0)'], $logger->results['shutdown.exitCode.a.phptx']);

Assert::same([Test::PASSED, null], $logger->results['shutdown.exitCode.b.phptx']);

Assert::same([Test::SKIPPED, 'just skipping'], $logger->results['skip.phptx']);

Assert::same(Test::FAILED, $logger->results['shutdown.assert.phptx'][0]);
Assert::match("Failed: 'b' should be%A%", Tester\Dumper::removeColors($logger->results['shutdown.assert.phptx'][1]));
