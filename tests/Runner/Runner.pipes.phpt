<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Runner\Test;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';

ini_set('memory_limit', '-1');

if (PHP_OS_FAMILY !== 'Windows' || PHP_VERSION_ID >= 80500) {
	// elsewhere one check reads all output that keeps coming, so a writing test can delay the others for seconds
	Tester\Environment::skip('Tests the reading of output on Windows with PHP < 8.5.');
} elseif (!Closure::bind(fn() => Tester\Runner\Job::canPoll(), null, Tester\Runner\Job::class)()) {
	Tester\Environment::skip('Reading the output of a running process blocks here, so the time limit cannot be enforced.');
}


class Logger implements Tester\Runner\OutputHandler
{
	/** @var array<string, array{int, float, float}>  [result, time of finish, duration] */
	public array $results = [];
	private float $start;


	public function prepare(Test $test): void
	{
	}


	public function finish(Test $test): void
	{
		$this->results[basename($test->getFile(), '.phptx') . count($this->results)] = [
			$test->getResult(),
			microtime(true) - $this->start,
			$test->getDuration(),
		];
	}


	public function begin(): void
	{
		$this->start = microtime(true);
	}


	public function end(): void
	{
	}
}


test('continuously writing processes starve neither other tests nor the time limit', function () {
	$runner = new Tester\Runner\Runner(createInterpreter());
	$runner->threadCount = 4;
	$runner->paths = array_fill(0, 2, __DIR__ . '/pipes/endless.phptx');
	$runner->paths[] = __DIR__ . '/pipes/sleep.phptx';
	array_push($runner->paths, ...array_fill(0, 4, __DIR__ . '/pipes/short.phptx'));
	$runner->timeout = 4; // long enough for slow machines: starting a process beside writers that use all processors takes up to 1 s
	$runner->outputHandlers[] = $logger = new Logger;
	$runner->run();

	Assert::count(7, $logger->results);
	$lastShort = 0;
	foreach ($logger->results as $name => [$result, $time, $duration]) {
		if (str_starts_with($name, 'short')) {
			Assert::same(Test::Passed, $result);
			$lastShort = max($lastShort, $time);
		} else {
			Assert::same(Test::Failed, $result);
			Assert::true($duration >= 4 && $duration < 7, "$name took $duration");
		}
	}

	Assert::true($lastShort < 4, "the short tests were finished after $lastShort");
});
