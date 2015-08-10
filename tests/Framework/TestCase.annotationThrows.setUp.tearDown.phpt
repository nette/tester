<?php

use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';


class SuccessTestCase extends TestCase
{
	/** @throws Exception  SuccessTestCase::testMe */
	public function testMe()
	{
		throw new Exception(__METHOD__);
	}
}

$test = new SuccessTestCase;
$test->run();



class FailingTestCase extends TestCase
{
	/** @throws RuntimeException  Wrong message */
	public function testMe()
	{
		throw new Exception(__METHOD__);
	}
}

Assert::exception(function () {
	$test = new FailingTestCase;
	$test->run();
}, 'Tester\AssertException', 'RuntimeException was expected but got Exception (FailingTestCase::testMe) in testMe()');



class SuccessButSetUpFails extends SuccessTestCase
{
	public function setUp()
	{
		throw new Exception(__METHOD__);
	}
}

Assert::exception(function () {
	$test = new SuccessButSetUpFails;
	$test->run();
}, 'Exception', 'SuccessButSetUpFails::setUp');



class SuccessButTearDownFails extends SuccessTestCase
{
	public function tearDown()
	{
		throw new Exception(__METHOD__);
	}
}

Assert::exception(function () {
	$test = new SuccessButTearDownFails;
	$test->run();
}, 'Exception', 'SuccessButTearDownFails::tearDown');



class FailingAndSetUpFails extends FailingTestCase
{
	public function setUp()
	{
		throw new Exception(__METHOD__);
	}
}

Assert::exception(function () {
	$test = new FailingAndSetUpFails;
	$test->run();
}, 'Exception', 'FailingAndSetUpFails::setUp');



class FailingAndTearDownFails extends FailingTestCase
{
	public function tearDown()
	{
		throw new Exception(__METHOD__);
	}
}

// tearDown() exception is never thrown when @throws assertion fails
Assert::exception(function () {
	$test = new FailingAndTearDownFails;
	$test->run();
}, 'Tester\AssertException', 'RuntimeException was expected but got Exception (FailingTestCase::testMe) in testMe()');
