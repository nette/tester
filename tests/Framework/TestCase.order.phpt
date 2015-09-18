<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class SuccessTest extends Tester\TestCase
{
	public static $order;

	protected function setUp()
	{
		self::$order[] = __METHOD__;
	}

	protected function tearDown()
	{
		self::$order[] = __METHOD__;
	}

	public function testPublic()
	{
		self::$order[] = __METHOD__;
	}

	public static function testPublicStatic()
	{
		self::$order[] = __METHOD__;
	}

}

$test = new SuccessTest;
$test->run();

Assert::same(array(
	'SuccessTest::setUp',
	'SuccessTest::testPublic',
	'SuccessTest::tearDown',
	'SuccessTest::setUp',
	'SuccessTest::testPublicStatic',
	'SuccessTest::tearDown',
), SuccessTest::$order);



class FailingTest extends Tester\TestCase
{
	public static $order;

	protected function setUp()
	{
		self::$order[] = __METHOD__;
	}

	protected function tearDown()
	{
		self::$order[] = __METHOD__;
	}

	public function testPublic()
	{
		self::$order[] = __METHOD__;
		Assert::fail('STOP');
	}

	public static function testPublicStatic()
	{
		self::$order[] = __METHOD__;
		Assert::fail('STOP');
	}

	public function testPublicError()
	{
		self::$order[] = __METHOD__;
		trigger_error('STOP');
	}

	public static function testPublicErrorStatic()
	{
		self::$order[] = __METHOD__;
		trigger_error('STOP');
	}

}


$test = new FailingTest;

Assert::exception(function () use ($test) {
	$test->run('testPublic');
}, 'Tester\AssertException');

Assert::exception(function () use ($test) {
	$test->run('testPublicStatic');
}, 'Tester\AssertException');


set_error_handler(function () {
	// Throwing an exception emulates test-interruption as a standard Environment handler.
	throw new \ErrorException;
});

Assert::exception(function () use ($test) {
	$test->run('testPublicError');
}, 'ErrorException');

Assert::exception(function () use ($test) {
	$test->run('testPublicErrorStatic');
}, 'ErrorException');

restore_error_handler();



Assert::same(array(
	'FailingTest::setUp',
	'FailingTest::testPublic',
	'FailingTest::setUp',
	'FailingTest::testPublicStatic',
	'FailingTest::setUp',
	'FailingTest::testPublicError',
	'FailingTest::setUp',
	'FailingTest::testPublicErrorStatic',
), FailingTest::$order);
