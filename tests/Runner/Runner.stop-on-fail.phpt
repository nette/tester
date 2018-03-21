<?php

declare(strict_types=1);

use Tester\Assert;
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


	public function prepare(Test $test): void
	{
	}


	public function finish(Test $test): void
	{
		$this->results[] = [$test->getResult(), basename($test->getFile())];
	}


	public function begin(): void
	{
	}


	public function end(): void
	{
	}
}

$interpreter = createInterpreter();


// Normal stop on the end
test(function () use ($interpreter) {
	$runner = new Runner($interpreter);
	$runner->outputHandlers[] = $logger = new Logger;
	$runner->paths = [
		__DIR__ . '/stop-on-fail/init-fail.phptx',
		__DIR__ . '/stop-on-fail/runtime-fail.phptx',
		__DIR__ . '/stop-on-fail/pass.phptx',
	];

	Assert::false($runner->run());
	Assert::same([
		[Test::FAILED, 'init-fail.phptx'],
		[Test::FAILED, 'runtime-fail.phptx'],
		[Test::PASSED, 'pass.phptx'],
	], $logger->results);
});


// Stop in initial phase
test(function () use ($interpreter) {
	$runner = new Runner($interpreter);
	$runner->outputHandlers[] = $logger = new Logger;
	$runner->stopOnFail = true;
	$runner->paths = [
		__DIR__ . '/stop-on-fail/init-fail.phptx',
		__DIR__ . '/stop-on-fail/pass.phptx',
	];

	Assert::false($runner->run());
	Assert::same([
		[Test::FAILED, 'init-fail.phptx'],
	], $logger->results);
});


// Stop in run-time
test(function () use ($interpreter) {
	$runner = new Runner($interpreter);
	$runner->outputHandlers[] = $logger = new Logger;
	$runner->stopOnFail = true;
	$runner->paths = [
		__DIR__ . '/stop-on-fail/runtime-fail.phptx',
		__DIR__ . '/stop-on-fail/pass.phptx',
	];

	Assert::false($runner->run());
	Assert::same([
		[Test::FAILED, 'runtime-fail.phptx'],
	], $logger->results);
});
