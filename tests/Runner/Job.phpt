<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Runner\Job;
use Tester\Runner\Test;

require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../bootstrap.php';


test(function () {
	$test = (new Test('Job.test.phptx'))->withArguments(['one', 'two' => 1])->withArguments(['three', 'two' => 2]);
	$job = new Job($test, createInterpreter());
	$job->run($job::RUN_COLLECT_ERRORS);

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
