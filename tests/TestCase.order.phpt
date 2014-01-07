<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class MyTest extends Tester\TestCase
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


$test = new MyTest;
$test->run();

Assert::same(array(
	'MyTest::setUp',
	'MyTest::testPublic',
	'MyTest::tearDown',
	'MyTest::setUp',
	'MyTest::testPublicStatic',
	'MyTest::tearDown',
), MyTest::$order);
