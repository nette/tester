<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Runner\{Job, Test};

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/pipes/data.php';


/** Replaces the pipe of a job. */
class FakePipe
{
	public static array|false $stat = false;
	public static string $data = '';
	public static int $reads = 0;
	public $context;


	public function stream_open(): bool
	{
		return true;
	}


	public function stream_stat(): array|false
	{
		return self::$stat;
	}


	public function stream_read(int $count): string
	{
		self::$reads++;
		$res = substr(self::$data, 0, $count);
		self::$data = substr(self::$data, strlen($res));
		return $res;
	}


	public function stream_eof(): bool
	{
		return self::$data === '';
	}
}

stream_wrapper_register('fakepipe', FakePipe::class);


function pollFake(array|false $stat, string $data): array
{
	[FakePipe::$stat, FakePipe::$data, FakePipe::$reads] = [$stat, $data, 0];
	$job = new Job(new Test('x'), createInterpreter());
	$access = Closure::bind(function () use ($job) {
		Job::$canPoll = $job->polled = true;
		$job->stdout = fopen('fakepipe://', 'r');
		$job->poll();
		$res = [$job->test->stdout, $job->polled, Job::$canPoll, $job->idleChecks];
		Job::$canPoll = null;
		return $res;
	}, null, Job::class);
	return $access();
}


test('unexpected results return to blocking reading, which is not repeated', function () {
	Assert::same(['', false, false, 50], pollFake(false, 'data')); // failed fstat()
	Assert::same(['', false, false, 50], pollFake(['size' => -5], 'data')); // invalid size
	Assert::same(['', false, false, 50], pollFake(['size' => 10], '')); // no progress
	Assert::same(0, (function () {
		pollFake(['size' => 0], 'data');
		return FakePipe::$reads;
	})()); // an empty pipe is not read
});


test('reading is limited', function () {
	[$stdout, $polled, , $idle] = pollFake(['size' => PHP_INT_MAX], str_repeat('x', 1_000_000));
	Assert::same(65536, strlen($stdout));
	Assert::true($polled);
	Assert::same(0, $idle);
});


test('output is complete when the pipe cannot be polled', function () {
	$run = Closure::bind(function (callable $before) {
		Job::$canPoll = false;
		$job = new Job(
			(new Test(__DIR__ . '/pipes/emit.phptx'))->withArguments(['out' => '300001', 'err' => '7', 'chunk' => '1000']),
			createInterpreter(),
		);
		$job->run(async: true);
		$before($job);
		while ($job->isRunning()) {
			Job::waitForActivity([$job]);
		}

		Job::$canPoll = null;
		return $job->getTest();
	}, null, Job::class);

	$test = $run(function () {});
	Assert::same(md5(pipeData(0, 0, 300_001)), md5($test->stdout));
	Assert::same(md5(pipeData(1, 0, 7)), md5($test->stderr));
});


/** Real pipe whose fstat() fails. */
class BrokenPipe
{
	public static $pipe;
	public $context;


	public function stream_open(): bool
	{
		return true;
	}


	public function stream_stat(): array|false
	{
		return false;
	}


	public function stream_read(int $count): string
	{
		return (string) fread(self::$pipe, $count);
	}


	public function stream_eof(): bool
	{
		return feof(self::$pipe);
	}


	public function stream_set_option(): bool
	{
		return false;
	}
}

stream_wrapper_register('brokenpipe', BrokenPipe::class);


test('a failure concerns the job itself and jobs started later, the other running jobs are still polled', function () {
	$run = Closure::bind(function () {
		if (PHP_OS_FAMILY === 'Windows' && PHP_VERSION_ID < 80500) {
			Job::$canPoll = null;
			if (!Job::canPoll()) {
				return null;
			}
		} else {
			return null;
		}

		$create = fn(string $sleep) => new Job((new Test(__DIR__ . '/pipes/emit.phptx'))
			->withArguments(['out' => '200001', 'err' => '0', 'chunk' => '5000', 'sleep' => $sleep]), createInterpreter());
		$jobs = [$create('0'), $create('100'), $create('100')];
		foreach ($jobs as $job) {
			$job->run(async: true);
		}

		BrokenPipe::$pipe = $jobs[0]->stdout;
		$jobs[0]->stdout = fopen('brokenpipe://', 'r');
		$jobs[0]->isRunning();
		$res = ['polled' => array_map(fn($job) => $job->polled, $jobs), 'canPoll' => Job::$canPoll];
		$late = $create('0');
		$late->run(async: true);
		$res['late'] = $late->polled;
		$jobs[] = $late;
		$running = $jobs;
		while ($running = array_filter($running, fn($job) => $job->isRunning())) {
			Job::waitForActivity($running);
		}

		$res['stillPolled'] = array_map(fn($job) => $job->polled, $jobs);
		$res['output'] = array_map(fn($job) => md5($job->getTest()->stdout), $jobs);
		Job::$canPoll = null;
		return $res;
	}, null, Job::class);

	if ($res = $run()) {
		$hash = md5(pipeData(0, 0, 200_001));
		Assert::same(
			[
				'polled' => [false, true, true],
				'canPoll' => false,
				'late' => false,
				'stillPolled' => [false, true, true, false],
				'output' => [$hash, $hash, $hash, $hash],
			],
			$res,
		);
	} else {
		Assert::true(true); // nothing to be polled on this platform
	}
});
