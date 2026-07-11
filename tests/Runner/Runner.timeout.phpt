<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Runner\Test;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';

if (PHP_OS_FAMILY === 'Windows' && PHP_VERSION_ID < 80500) {
	Tester\Environment::skip('Requires PHP 8.5 on Windows, where reading the output of a running process does not block.');
}


class Logger implements Tester\Runner\OutputHandler
{
	public $results = [];


	public function prepare(Test $test): void
	{
	}


	public function finish(Test $test): void
	{
		$this->results[] = [basename($test->getFile()), $test->getResult(), $test->message];
	}


	public function begin(): void
	{
	}


	public function end(): void
	{
	}
}


test('tests exceeding --timeout limit are terminated and fail', function () {
	$runner = new Tester\Runner\Runner(createInterpreter());
	$runner->paths[] = __DIR__ . '/timeout/fast.phptx';
	$runner->paths[] = __DIR__ . '/timeout/sleep.phptx';
	$runner->timeout = 0.5;
	$runner->outputHandlers[] = $logger = new Logger;
	$runner->run();

	sort($logger->results);
	Assert::same([
		['fast.phptx', Test::Passed, null],
		['sleep.phptx', Test::Failed, 'Timed out after 0.5 s'],
	], $logger->results);
});


test('@timeout annotation overrides the runner default', function () {
	$runner = new Tester\Runner\Runner(createInterpreter());
	$runner->paths[] = __DIR__ . '/timeout/annotated.phptx';
	$runner->outputHandlers[] = $logger = new Logger;
	$runner->run();

	Assert::same([
		['annotated.phptx', Test::Failed, 'Timed out after 0.5 s'],
	], $logger->results);
});
