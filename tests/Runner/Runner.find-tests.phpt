<?php

/**
 * @phpversion 5.4  Requires constant PHP_BINARY available since PHP 5.4.0
 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';


$runner = new Tester\Runner\Runner(createInterpreter());

if (defined('HHVM_VERSION') && version_compare(HHVM_VERSION, '3.4.0-dev', '<')) {
	$jobs = call_user_func(function () use ($runner) {
		// Workaround for missing Closure::bindTo()
		$findTests = new ReflectionMethod($runner, 'findTests');
		$findTests->setAccessible(TRUE);

		$jobs = new ReflectionProperty($runner, 'jobs');
		$jobs->setAccessible(TRUE);

		$findTests->invokeArgs($runner, [__DIR__ . '/find-tests/*.phptx']);
		return $jobs->getValue($runner);
	});

} else {
	$jobs = Assert::with($runner, function () {
		$this->findTests(__DIR__ . '/find-tests/*.phptx');
		return $this->jobs;
	});
}

Assert::count(1, $jobs);
