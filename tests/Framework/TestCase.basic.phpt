<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class TestCaseTest extends Tester\TestCase
{
	public function testAssertion()
	{
		Assert::true(FALSE);
	}

	public function testPass()
	{
		Assert::true(TRUE);
	}
}

class TestCaseTearDownException extends TestCaseTest
{
	public function tearDown()
	{
		throw new RuntimeException("Error in tearDown");
	}
}


Assert::exception(function () {
	$test = new TestCaseTest;
	$test->run('testAssertion');
}, 'Tester\AssertException', 'FALSE should be TRUE in testAssertion()');


$test = new TestCaseTearDownException;
Assert::exception(function () use ($test) {
	$test->tearDown();
}, 'RuntimeException');


Assert::exception(function () use ($test) {
	$test->run('testAssertion');
},
'Tester\TestCaseException',
"tearDown() phase failed in testAssertion()");


Assert::exception(function () use ($test) {
	$test->run('testPass');
},
'Tester\TestCaseException',
"tearDown() phase failed in testPass()");
