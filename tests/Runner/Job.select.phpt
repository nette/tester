<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Runner\{Job, Test};

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/Test.php';

if (PHP_OS_FAMILY === 'Windows' && PHP_VERSION_ID < 80500) {
	Tester\Environment::skip('Requires PHP 8.5 on Windows, where reading the output of a running process does not block.');
}

ini_set('memory_limit', '-1');


function createJob(string $file, string ...$args): Job
{
	return new Job((new Test(__DIR__ . "/select/$file.phptx"))->withArguments($args), createInterpreter());
}


/** @param  Job[]  $jobs */
function finish(array $jobs): int
{
	for ($count = 0; $jobs = array_filter($jobs, fn($job) => $job->isRunning()); $count++) {
		Job::waitForActivity($jobs);
	}
	return $count;
}


function checkOutput(Job $job, int $lines): void
{
	Assert::same(0, $job->getExitCode());
	$expected = [
		implode('', array_map(fn($i) => sprintf("%099d\n", $i), range(0, $lines - 1))),
		implode('', array_map(fn($i) => sprintf("%099d\n", -$i), range(0, $lines - 1))),
	];
	Assert::same(array_map('md5', $expected), array_map('md5', [$job->getTest()->stdout, $job->getTest()->stderr]));
}


test('output of more jobs than one stream_select() can watch', function () {
	$jobs = [];
	for ($i = 0; $i < 70; $i++) {
		$jobs[] = $job = createJob('emit', lines: '200', sleep: '500');
		$job->run(async: true);
	}
	finish($jobs);
	foreach ($jobs as $job) {
		checkOutput($job, 200);
	}
});


test('large output to both streams', function () {
	$jobs = [];
	for ($i = 0; $i < 4; $i++) {
		$jobs[] = $job = createJob('emit', lines: '10000');
		$job->run(async: true);
	}
	finish($jobs);
	foreach ($jobs as $job) {
		checkOutput($job, 10_000);
	}
});


test('readiness is used only once', function () {
	$job = createJob('emit', lines: '3000');
	$job->run(async: true);
	for ($i = 0; $job->isRunning(); $i++) {
		if ($i % 3) { // sometimes several times, sometimes not at all
			Job::waitForActivity([$job]);
			Job::waitForActivity([$job]);
		}
	}
	checkOutput($job, 3000);
});


test('a process that closed its output is not checked all the time', function () {
	$job = createJob('emit', lines: '10', after: '1000');
	$job->run(async: true);
	$count = finish([$job]);
	checkOutput($job, 10);
	if (PHP_OS_FAMILY === 'Windows') {
		Assert::true($count < 500, "$count checks");
	}
});


test('continuously writing processes starve neither the others nor the time limit', function () {
	if (PHP_OS_FAMILY !== 'Windows') {
		return; // elsewhere one check reads all output that keeps coming, so a writing test can delay the others for seconds
	}

	$jobs = [];
	foreach ([createJob('endless'), createJob('endless'), $short = createJob('emit', lines: '10')] as $job) {
		$job->setTimeout(3); // long enough to start a process beside the writers on a slow machine
		$job->run(async: true);
		$jobs[] = $job;
	}
	finish($jobs);
	checkOutput($short, 10);
	foreach ($jobs as $job) {
		Assert::same($job !== $short, $job->isTimedOut());
		Assert::true($job->getDuration() < 6, (string) $job->getDuration());
	}
});


test('synchronous jobs', function () {
	$job = createJob('emit', lines: '10000');
	$job->run();
	checkOutput($job, 10_000);

	$job = createJob('emit', lines: '10000');
	$job->setTimeout(60);
	$job->run();
	checkOutput($job, 10_000);

	$job = createJob('endless');
	$job->setTimeout(1);
	$job->run();
	Assert::true($job->isTimedOut());
	Assert::true($job->getDuration() < 4, (string) $job->getDuration());
});
