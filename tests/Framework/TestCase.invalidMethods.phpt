<?php

declare(strict_types=1);

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

Assert::exception(
	fn() => $test->runTest('testProtected'),
	Tester\TestCaseException::class,
	'Method testProtected is not public. Make it public or rename it.',
);

Assert::exception(
	fn() => $test->runTest('testPrivate'),
	Tester\TestCaseException::class,
	'Method testPrivate is not public. Make it public or rename it.',
);

Assert::exception(
	fn() => $test->runTest('testUndefined'),
	Tester\TestCaseException::class,
	"Method 'testUndefined' does not exist.",
);

Assert::exception(
	fn() => $test->runTest('notTesting'),
	Tester\TestCaseException::class,
	"Method 'notTesting' is not a testing method.",
);

Assert::exception(
	fn() => $test->runTest('notTestingUndefined'),
	Tester\TestCaseException::class,
	"Method 'notTestingUndefined' does not exist.",
);
