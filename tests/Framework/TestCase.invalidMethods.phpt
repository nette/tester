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

Assert::exception(function() use ($test) {
	$test->run('testProtected');
}, 'Tester\TestCaseException', 'Method testProtected is not public. Make it public or rename it.');

Assert::exception(function() use ($test) {
	$test->run('testPrivate');
}, 'Tester\TestCaseException', 'Method testPrivate is not public. Make it public or rename it.');

Assert::exception(function() use ($test) {
	$test->run('testUndefined');
}, 'Tester\TestCaseException', "Method 'testUndefined' does not exist or it is not a testing method.");

Assert::exception(function() use ($test) {
	$test->run('notTesting');
}, 'Tester\TestCaseException', "Method 'notTesting' does not exist or it is not a testing method.");

Assert::exception(function() use ($test) {
	$test->run('notTestingUndefined');
}, 'Tester\TestCaseException', "Method 'notTestingUndefined' does not exist or it is not a testing method.");
