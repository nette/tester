<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class TestCaseTest extends Tester\TestCase
{
	public function testAssertion()
	{
		Assert::true(false);
	}
}

class TestCaseTearDownException extends TestCaseTest
{
	public function tearDown()
	{
		throw new RuntimeException;
	}
}


Assert::exception(
	function () {
		$test = new TestCaseTest;
		$test->runTest('testAssertion');
	},
	Tester\AssertException::class,
	'false should be true in testAssertion()',
);


$test = new TestCaseTearDownException;
Assert::exception(
	fn() => $test->tearDown(),
	RuntimeException::class,
);

Assert::exception(
	fn() => $test->runTest('testAssertion'),
	Tester\AssertException::class,
	'false should be true in testAssertion()',
);
