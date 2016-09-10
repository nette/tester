<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/TestInstance.php';
require __DIR__ . '/../../src/Runner/Runner.php';


$runner = new Tester\Runner\Runner(createInterpreter());

if (defined('HHVM_VERSION') && version_compare(HHVM_VERSION, '3.4.0-dev', '<')) {
	$tests = call_user_func(function () use ($runner) {
		// Workaround for missing Closure::bindTo()
		$findTests = new ReflectionMethod($runner, 'findTests');
		$findTests->setAccessible(TRUE);

		$testInstances = new ReflectionProperty($runner, 'testInstances');
		$testInstances->setAccessible(TRUE);

		$findTests->invokeArgs($runner, [__DIR__ . '/find-tests/*.phptx']);
		return $testInstances->getValue($runner);
	});

} else {
	$tests = Assert::with($runner, function () {
		$this->findTests(__DIR__ . '/find-tests/*.phptx');
		return $this->testInstances;
	});
}

Assert::count(1, $tests);
