<?php

use Tester\Assert;
use Tester\Helpers;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';


$runner = new Tester\Runner\Runner(createInterpreter());

if (defined('HHVM_VERSION') && version_compare(HHVM_VERSION, '3.4.0-dev', '<')) {
	$tests = call_user_func(function () use ($runner) {
		// Workaround for missing Closure::bindTo()
		$results = new ReflectionProperty($runner, 'results');
		$results->setAccessible(TRUE);

		$findTests = new ReflectionMethod($runner, 'findTests');
		$findTests->setAccessible(TRUE);

		$jobs = new ReflectionProperty($runner, 'jobs');
		$jobs->setAccessible(TRUE);

		$results->setValue($runner, [$runner::PASSED => 0, $runner::SKIPPED => 0, $runner::FAILED => 0]);
		$findTests->invokeArgs($runner, [__DIR__ . '/multiple/*.phptx']);
		return $jobs->getValue($runner);
	});

} else {
	$tests = Assert::with($runner, function () {
		$this->results = [self::PASSED => 0, self::SKIPPED => 0, self::FAILED => 0];
		$this->findTests(__DIR__ . '/multiple/*.phptx');
		return $this->jobs;
	});
}


foreach ($tests as $i => $job) {
	$tests[$i] = [basename($job->getFile()), $job->getArguments()];
}
sort($tests);

$path = __DIR__ . DIRECTORY_SEPARATOR . 'multiple' . DIRECTORY_SEPARATOR;

Assert::same([
	['dataProvider.multiple.phptx', ['dataprovider' => "bar|$path../../Framework/fixtures/dataprovider.ini", 'multiple' => 0]],
	['dataProvider.multiple.phptx', ['dataprovider' => "bar|$path../../Framework/fixtures/dataprovider.ini", 'multiple' => 1]],
	['dataProvider.multiple.phptx', ['dataprovider' => "foo|$path../../Framework/fixtures/dataprovider.ini", 'multiple' => 0]],
	['dataProvider.multiple.phptx', ['dataprovider' => "foo|$path../../Framework/fixtures/dataprovider.ini", 'multiple' => 1]],
	['dataProvider.phptx', ['dataprovider' => "bar|$path../../Framework/fixtures/dataprovider.ini"]],
	['dataProvider.phptx', ['dataprovider' => "foo|$path../../Framework/fixtures/dataprovider.ini"]],
	['dataProvider.query.phptx', ['dataprovider' => "foo 2.2.3|$path../../Framework/fixtures/dataprovider.query.ini"]],
	['dataProvider.query.phptx', ['dataprovider' => "foo 3 xxx|$path../../Framework/fixtures/dataprovider.query.ini"]],
	['multiple.phptx', ['multiple' => 0]],
	['multiple.phptx', ['multiple' => 1]],
	['testcase.phptx', ['method' => 'test1']],
	['testcase.phptx', ['method' => 'testBar']],
	['testcase.phptx', ['method' => 'testFoo']],
	['testcase.phptx', ['method' => 'testPrivate']],
	['testcase.phptx', ['method' => 'testProtected']],
	['testcase.phptx', ['method' => 'test_foo']],
], $tests);
