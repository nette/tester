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
		$this->results[] = [$testInstance->getResult(), basename($testInstance->getTestName())];
	}

	function begin(array $testInstances) {}
	function end() {}
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

	Assert::notSame(0, $runner->run());
	Assert::same([
		[Runner::FAILED, 'init-fail.phptx'],
		[Runner::FAILED, 'runtime-fail.phptx'],
		[Runner::PASSED, 'pass.phptx'],
	], $logger->results);
});


// Stop in initial phase
test(function () use ($interpreter) {
	$runner = new Runner($interpreter);
	$runner->outputHandlers[] = $logger = new Logger;
	$runner->stopOnFail = TRUE;
	$runner->paths = [
		__DIR__ . '/stop-on-fail/init-fail.phptx',
		__DIR__ . '/stop-on-fail/pass.phptx',
	];

	Assert::notSame(0, $runner->run());
	Assert::same([
		[Runner::FAILED, 'init-fail.phptx'],
	], $logger->results);
});


// Stop in run-time
test(function () use ($interpreter) {
	$runner = new Runner($interpreter);
	$runner->outputHandlers[] = $logger = new Logger;
	$runner->stopOnFail = TRUE;
	$runner->paths = [
		__DIR__ . '/stop-on-fail/runtime-fail.phptx',
		__DIR__ . '/stop-on-fail/pass.phptx',
	];

	Assert::notSame(0, $runner->run());
	Assert::same([
		[Runner::FAILED, 'runtime-fail.phptx'],
	], $logger->results);
});
