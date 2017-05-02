<?php

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

	function result($testName, $result, $message)
	{
		$this->results[] = [$result, basename($testName)];
	}

	function begin() {}
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
		[Test::FAILED, 'init-fail.phptx'],
		[Test::FAILED, 'runtime-fail.phptx'],
		[Test::PASSED, 'pass.phptx'],
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
		[Test::FAILED, 'init-fail.phptx'],
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
		[Test::FAILED, 'runtime-fail.phptx'],
	], $logger->results);
});
