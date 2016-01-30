<?php

/**
 * @phpversion 5.4  Requires constant PHP_BINARY available since PHP 5.4.0
 */

use Tester\Assert;
use Tester\Runner\Job;

require __DIR__ . '/../bootstrap.php';


test(function () {
	$job = new Job($file = 'Job.test.phptx', createInterpreter(), $args = array('one', 'two'));
	$job->run($job::RUN_COLLECT_ERRORS);

	Assert::false($job->isRunning());
	Assert::same($file, $job->getFile());
	Assert::same($args, $job->getArguments());
	Assert::same(231, $job->getExitCode());

	if (defined('PHPDBG_VERSION') && PHP_VERSION_ID === 70000) { // bug #71056
		Assert::same('Args: one, twoError1-outputError2', $job->getOutput());
		Assert::same('', $job->getErrorOutput());
	} else {
		Assert::same('Args: one, two-output', $job->getOutput());
		Assert::same('Error1Error2', $job->getErrorOutput());
	}

	if (PHP_SAPI !== 'cli') {
		Assert::contains('Nette Tester', $job->getHeaders());
	}
});
