<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class MyTest extends Tester\TestCase
{
	protected function testProtected()
	{
	}

	private function testPrivate()
	{
	}

	public function notTesting()
	{
	}

}


$test = new MyTest;

Assert::exception(function () use ($test) {
	$test->runTest('testProtected');
}, 'Tester\TestCaseException', 'Method testProtected is not public. Make it public or rename it.');

Assert::exception(function () use ($test) {
	$test->runTest('testPrivate');
}, 'Tester\TestCaseException', 'Method testPrivate is not public. Make it public or rename it.');

Assert::exception(function () use ($test) {
	$test->runTest('testUndefined');
}, 'Tester\TestCaseException', "Method 'testUndefined' does not exist.");

Assert::exception(function () use ($test) {
	$test->runTest('notTesting');
}, 'Tester\TestCaseException', "Method 'notTesting' is not a testing method.");

Assert::exception(function () use ($test) {
	$test->runTest('notTestingUndefined');
}, 'Tester\TestCaseException', "Method 'notTestingUndefined' does not exist.");
