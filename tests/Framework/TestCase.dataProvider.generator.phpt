<?php

/**
 * @phpversion 5.5
 */

use Tester\Assert;
use Tester\Environment;

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
$test->run('testDataProviderGenerator');
Assert::same([
	['MyTest::testDataProviderGenerator', [0]],
	['MyTest::testDataProviderGenerator', [1]],
	['MyTest::testDataProviderGenerator', [2]],
	['MyTest::testDataProviderGenerator', [3]],
], $test->order);
