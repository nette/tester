<?php

/**
 * @phpversion 5.4  Requires constant PHP_BINARY available since PHP 5.4.0
 */

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
	['dataProvider.multiple.phptx', [Helpers::escapeArg("--dataprovider=bar|$path../../Framework/fixtures/dataprovider.ini"), Helpers::escapeArg('--multiple=0')]],
	['dataProvider.multiple.phptx', [Helpers::escapeArg("--dataprovider=bar|$path../../Framework/fixtures/dataprovider.ini"), Helpers::escapeArg('--multiple=1')]],
	['dataProvider.multiple.phptx', [Helpers::escapeArg("--dataprovider=foo|$path../../Framework/fixtures/dataprovider.ini"), Helpers::escapeArg('--multiple=0')]],
	['dataProvider.multiple.phptx', [Helpers::escapeArg("--dataprovider=foo|$path../../Framework/fixtures/dataprovider.ini"), Helpers::escapeArg('--multiple=1')]],
	['dataProvider.phptx', [Helpers::escapeArg("--dataprovider=bar|$path../../Framework/fixtures/dataprovider.ini")]],
	['dataProvider.phptx', [Helpers::escapeArg("--dataprovider=foo|$path../../Framework/fixtures/dataprovider.ini")]],
	['dataProvider.query.phptx', [Helpers::escapeArg("--dataprovider=foo 2.2.3|$path../../Framework/fixtures/dataprovider.query.ini")]],
	['dataProvider.query.phptx', [Helpers::escapeArg("--dataprovider=foo 3 xxx|$path../../Framework/fixtures/dataprovider.query.ini")]],
	['multiple.phptx', [Helpers::escapeArg('--multiple=0')]],
	['multiple.phptx', [Helpers::escapeArg('--multiple=1')]],
	['testcase.phptx', [Helpers::escapeArg('--method=test1')]],
	['testcase.phptx', [Helpers::escapeArg('--method=testBar')]],
	['testcase.phptx', [Helpers::escapeArg('--method=testFoo')]],
	['testcase.phptx', [Helpers::escapeArg('--method=testPrivate')]],
	['testcase.phptx', [Helpers::escapeArg('--method=testProtected')]],
	['testcase.phptx', [Helpers::escapeArg('--method=test_foo')]],
], $tests);
