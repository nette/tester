<?php

declare(strict_types=1);

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

(new SuccessTest)->run();

Assert::same([
	'SuccessTest::setUp',
	'SuccessTest::testPublic',
	'SuccessTest::tearDown',
	'SuccessTest::setUp',
	'SuccessTest::testPublicStatic',
	'SuccessTest::tearDown',
], SuccessTest::$order);



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
}


$test = new FailingTest;

Assert::exception(function () use ($test) {
	$test->runTest('testPublic');
}, Tester\AssertException::class);

Assert::exception(function () use ($test) {
	$test->runTest('testPublicStatic');
}, Tester\AssertException::class);


Assert::same([
	'FailingTest::setUp',
	'FailingTest::testPublic',
	'FailingTest::tearDown',
	'FailingTest::setUp',
	'FailingTest::testPublicStatic',
	'FailingTest::tearDown',
], FailingTest::$order);
