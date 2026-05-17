<?php declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class MultiFailureTest extends Tester\TestCase
{
	public static array $executed = [];


	public function testOne(): void
	{
		self::$executed[] = __METHOD__;
		Assert::true(false);
	}


	public function testTwo(): void
	{
		self::$executed[] = __METHOD__;
		Assert::false(true);
	}


	public function testThree(): void
	{
		self::$executed[] = __METHOD__;
	}
}


Assert::exception(
	fn() => (new MultiFailureTest)->run(),
	Tester\AssertException::class,
	'true should be false in testTwo()',
);

Assert::same(
	['MultiFailureTest::testOne', 'MultiFailureTest::testTwo', 'MultiFailureTest::testThree'],
	MultiFailureTest::$executed,
);
