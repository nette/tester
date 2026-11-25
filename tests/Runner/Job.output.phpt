<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Runner\{Job, Test};

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/pipes/data.php';

ini_set('memory_limit', '-1');


/** @param  array<array{int, int, int}>  $specs  [stdout, stderr, chunk] */
function checkOutput(array $specs, bool $async, ?float $timeout = null): void
{
	$interpreter = createInterpreter();
	$jobs = [];
	foreach ($specs as [$out, $err, $chunk]) {
		$test = (new Test(__DIR__ . '/pipes/emit.phptx'))->withArguments([
			'out' => (string) $out,
			'err' => (string) $err,
			'chunk' => (string) $chunk,
		]);
		$jobs[] = $job = new Job($test, $interpreter);
		$job->setTimeout($timeout);
		$job->run(async: $async);
	}

	$running = $jobs;
	while ($running = array_filter($running, fn($job) => $job->isRunning())) {
		Job::waitForActivity($running);
	}

	foreach ($jobs as $i => $job) {
		[$out, $err] = $specs[$i];
		$test = $job->getTest();
		Assert::same(0, $job->getExitCode());
		Assert::false($job->isTimedOut());
		Assert::same([$out, $err], [strlen($test->stdout), strlen($test->stderr)]);
		Assert::same(md5(pipeData(0, 0, $out)), md5($test->stdout));
		Assert::same(md5(pipeData(1, 0, $err)), md5($test->stderr));
	}
}


foreach (['async' => [true, null], 'sync' => [false, null], 'sync with timeout' => [false, 60.0]] as $name => [$async, $timeout]) {
	test("sizes around the buffers, $name", function () use ($async, $timeout) {
		foreach (array_chunk([0, 1, 4095, 4096, 4097, 8191, 8192, 8193, 65_535, 65_536, 65_537], 6) as $sizes) {
			checkOutput(array_map(fn($n) => [$n, $n, 8192], $sizes), $async, $timeout);
		}
	});

	test("large output, $name", function () use ($async, $timeout) {
		checkOutput([[1_000_001, 0, 8192], [0, 1_000_001, 8192], [700_003, 700_003, 777], [1_200_000, 5, 600_000]], $async, $timeout);
	});
}
