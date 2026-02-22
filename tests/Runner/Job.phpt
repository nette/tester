<?php

declare(strict_types=1);

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

	// PHPDBG has a bug where php://stderr writes are always routed to stdout (fd 1),
	// so stderr content appears interleaved in stdout in the order it was written
	if (defined('PHPDBG_VERSION')) {
		Assert::same('Args: one, --two=1, three, --two=2+stderr1+stdout+stderr2', $job->getTest()->stdout);
		Assert::same('', $job->getTest()->stderr);
	} else {
		Assert::same('Args: one, --two=1, three, --two=2+stdout', $job->getTest()->stdout);
		Assert::same('+stderr1+stderr2', $job->getTest()->stderr);
	}
	Assert::type('float', $job->getDuration());

	if (PHP_SAPI !== 'cli') {
		Assert::contains('Nette Tester', $job->getHeaders());
	}
});


test('appending title to a Test', function () {
	$testA = (new Test('Job.test.phptx'));
	Assert::null($testA->title);

	$testB = $testA->withTitle('title');
	Assert::notSame($testB, $testA);
	Assert::same('title', $testB->title);
});
