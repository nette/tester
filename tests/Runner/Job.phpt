<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Runner\Job;
use Tester\Runner\Test;

require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../bootstrap.php';


test('appending arguments to Test', function () {
	$test = (new Test('Job.test.phptx'))->withArguments(['one', 'two' => 1])->withArguments(['three', 'two' => 2]);
	$job = new Job($test, createInterpreter());
	$job->setTempDirectory(Tester\Helpers::prepareTempDir(sys_get_temp_dir()));
	$job->run();

	Assert::false($job->isRunning());
	Assert::same($test, $job->getTest());
	Assert::same(231, $job->getExitCode());

	Assert::same('Args: one, --two=1, three, --two=2+stdout', $job->getTest()->stdout);
	Assert::same('+stderr1+stderr2', $job->getTest()->stderr);
	Assert::type('float', $job->getDuration());

	if (PHP_SAPI !== 'cli') {
		Assert::contains('Nette Tester', $job->getHeaders());
	}
});


test('environment variables of the runner are restored', function () {
	putenv('TESTER_JOB_ENV=original');
	try {
		$job = new Job(new Test(__DIR__ . '/Job.env.phptx'), createInterpreter(), ['TESTER_JOB_ENV' => 'override', 'TESTER_JOB_NEW' => 'new']);
		$job->setTempDirectory(Tester\Helpers::prepareTempDir(sys_get_temp_dir()));
		$job->run();

		Assert::same('override|new', $job->getTest()->stdout);
		Assert::same('original', getenv('TESTER_JOB_ENV'));
		Assert::false(getenv('TESTER_JOB_NEW'));
	} finally {
		putenv('TESTER_JOB_ENV');
	}
});


test('appending title to a Test', function () {
	$testA = (new Test('Job.test.phptx'));
	Assert::null($testA->title);

	$testB = $testA->withTitle('title');
	Assert::notSame($testB, $testA);
	Assert::same('title', $testB->title);
});
