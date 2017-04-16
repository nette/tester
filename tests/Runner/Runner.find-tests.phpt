<?php

use Tester\Assert;
use Tester\Helpers;

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
		$findTests->invokeArgs($runner, [__DIR__ . '/find-tests']);
		return $jobs->getValue($runner);
	});

} else {
	$jobs = Helpers::with($runner, function () {
		$this->findTests(__DIR__ . '/find-tests/*.phptx');
		$this->findTests(__DIR__ . '/find-tests');
		return $this->jobs;
	});
}

Assert::count(2, $jobs);
