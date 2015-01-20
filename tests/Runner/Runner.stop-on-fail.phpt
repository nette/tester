<?php

/**
 * @phpversion 5.4  Requires constant PHP_BINARY available since PHP 5.4.0
 */

use Tester\Assert,
	Tester\Runner\Runner;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../Tester/Runner/OutputHandler.php';
require __DIR__ . '/../../Tester/Runner/TestHandler.php';
require __DIR__ . '/../../Tester/Runner/Runner.php';


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
test(function() use ($interpreter) {
	$runner = new Runner($interpreter);
	$runner->outputHandlers[] = $logger = new Logger;
	$runner->paths = [
		__DIR__ . '/stop-on-fail/init-fail.phptx',
		__DIR__ . '/stop-on-fail/runtime-fail.phptx',
		__DIR__ . '/stop-on-fail/pass.phptx',
	];

	Assert::notSame( 0, $runner->run() );
	Assert::same([
		[Runner::FAILED, 'init-fail.phptx'],
		[Runner::FAILED, 'runtime-fail.phptx'],
		[Runner::PASSED, 'pass.phptx'],
	], $logger->results);
});


// Stop in initial phase
test(function() use ($interpreter) {
	$runner = new Runner($interpreter);
	$runner->outputHandlers[] = $logger = new Logger;
	$runner->stopOnFail = TRUE;
	$runner->paths = [
		__DIR__ . '/stop-on-fail/init-fail.phptx',
		__DIR__ . '/stop-on-fail/pass.phptx',
	];

	Assert::notSame( 0, $runner->run() );
	Assert::same([
		[Runner::FAILED, 'init-fail.phptx'],
	], $logger->results);
});


// Stop in run-time
test(function() use ($interpreter) {
	$runner = new Runner($interpreter);
	$runner->outputHandlers[] = $logger = new Logger;
	$runner->stopOnFail = TRUE;
	$runner->paths = [
		__DIR__ . '/stop-on-fail/runtime-fail.phptx',
		__DIR__ . '/stop-on-fail/pass.phptx',
	];

	Assert::notSame( 0, $runner->run() );
	Assert::same([
		[Runner::FAILED, 'runtime-fail.phptx'],
	], $logger->results);
});
