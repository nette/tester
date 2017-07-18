<?php

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


Assert::exception(function () {
	$test = new TestCaseTest;
	$test->runTest('testAssertion');
}, 'Tester\AssertException', 'FALSE should be TRUE in testAssertion()');


$test = new TestCaseTearDownException;
Assert::exception(function () use ($test) {
	$test->tearDown();
}, 'RuntimeException');

Assert::exception(function () use ($test) {
	$test->runTest('testAssertion');
}, 'Tester\AssertException', 'FALSE should be TRUE in testAssertion()');
