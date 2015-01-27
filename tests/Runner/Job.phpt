<?php

/**
 * @phpversion 5.4  Requires constant PHP_BINARY available since PHP 5.4.0
 */

use Tester\Assert,
	Tester\Runner\Job;

require __DIR__ . '/../bootstrap.php';


test(function() {
	$job = new Job($file = 'Job.test.phptx', createInterpreter(), $args = array('one', 'two'));
	$job->run();

	Assert::false($job->isRunning());
	Assert::same($file, $job->getFile());
	Assert::same($args, $job->getArguments());
	Assert::same(231, $job->getExitCode());
	Assert::same('Args: one, two', $job->getOutput());
	if (PHP_SAPI !== 'cli') {
		Assert::contains('Nette Tester', $job->getHeaders());
	}
});
