<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

class SuccessfulTest extends Tester\TestCase
{
	public static $tornDown = 0;

	protected function tearDown()
	{
		self::$tornDown++;
	}

	public function testNothing()
	{
		Assert::true(TRUE);
	}
}

$test = new SuccessfulTest;

$test->run();
Assert::true(SuccessfulTest::$tornDown === 1);
