<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class MyTest extends Tester\TestCase
{
	public $order;


	public function dataProvider()
	{
		for ($i = 0; $i < 4; $i++) {
			yield [$i];
		}
	}


	/** @dataProvider dataProvider */
	public function testDataProviderGenerator($a)
	{
		$this->order[] = [__METHOD__, func_get_args()];
	}
}


$test = new MyTest;
$test->runTest('testDataProviderGenerator');
Assert::same([
	['MyTest::testDataProviderGenerator', [0]],
	['MyTest::testDataProviderGenerator', [1]],
	['MyTest::testDataProviderGenerator', [2]],
	['MyTest::testDataProviderGenerator', [3]],
], $test->order);
