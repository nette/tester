<?php

declare(strict_types=1);

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

(new SuccessTestCase)->run();



class FailingTestCase extends TestCase
{
	/** @throws RuntimeException  Wrong message */
	public function testMe()
	{
		throw new Exception(__METHOD__);
	}
}

Assert::exception(function () {
	(new FailingTestCase)->run();
}, Tester\AssertException::class, 'RuntimeException was expected but got Exception (FailingTestCase::testMe) in testMe()');



class SuccessButSetUpFails extends SuccessTestCase
{
	public function setUp()
	{
		throw new Exception(__METHOD__);
	}
}

Assert::exception(function () {
	(new SuccessButSetUpFails)->run();
}, Exception::class, 'SuccessButSetUpFails::setUp');



class SuccessButTearDownFails extends SuccessTestCase
{
	public function tearDown()
	{
		throw new Exception(__METHOD__);
	}
}

Assert::exception(function () {
	(new SuccessButTearDownFails)->run();
}, Exception::class, 'SuccessButTearDownFails::tearDown');



class FailingAndSetUpFails extends FailingTestCase
{
	public function setUp()
	{
		throw new Exception(__METHOD__);
	}
}

Assert::exception(function () {
	(new FailingAndSetUpFails)->run();
}, Exception::class, 'FailingAndSetUpFails::setUp');



class FailingAndTearDownFails extends FailingTestCase
{
	public function tearDown()
	{
		throw new Exception(__METHOD__);
	}
}

// tearDown() exception is never thrown when @throws assertion fails
Assert::exception(function () {
	(new FailingAndTearDownFails)->run();
}, Tester\AssertException::class, 'RuntimeException was expected but got Exception (FailingTestCase::testMe) in testMe()');
