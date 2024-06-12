<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Runner\Job;
use Tester\Runner\Test;

require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../bootstrap.php';


test('', function () {
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


test('Appending title to a Test object w/o initial title', function () {
	$testA = (new Test('Job.test.phptx'));
	Assert::null($testA->title);

	$testB = $testA->withAppendedTitle('title B');
	Assert::notSame($testB, $testA);
	Assert::same('title B', $testB->title);

	$testC = $testB->withAppendedTitle(" \t    title C  ");
	Assert::notSame($testC, $testB);
	Assert::same('title B title C', $testC->title);
});


test('Appending title to a Test object w/ initial title', function () {
	$testA = (new Test('Job.test.phptx', 'Initial title   '));
	Assert::same('Initial title', $testA->title);

	$testB = $testA->withAppendedTitle('   ');
	Assert::same('Initial title', $testB->title);

	$testC = $testB->withAppendedTitle(" \t    MEGATITLE  ");
	Assert::same('Initial title MEGATITLE', $testC->title);
});
