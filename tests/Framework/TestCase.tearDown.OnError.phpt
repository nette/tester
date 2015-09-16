<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

class ErroredTest extends Tester\TestCase
{
	public static $tornDown = 0;

	protected function tearDown()
	{
		self::$tornDown += 1;
	}

	public function testPublic()
	{
		++$a;
	}
}

$test = new ErroredTest;

set_error_handler(function() {
	// 2 would mean tearDown is executed repeatedly if tearDown fails
	Assert::true(ErroredTest::$tornDown === 1);
	return TRUE;
});

$test->run();
