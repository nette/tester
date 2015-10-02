<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class MyTest extends Tester\TestCase
{
	public $order;

	public function dataProvider()
	{
		$this->order[] = __METHOD__;
		return [
			[1, 2],
			[3, 4],
		];
	}

	public function dataProviderIterator()
	{
		$this->order[] = __METHOD__;
		return new \ArrayIterator([
			[1, 2],
			[3, 4],
		]);
	}

	/** @dataProvider dataProvider */
	public function testSingleDataProvider($a, $b)
	{
		$this->order[] = [__METHOD__, func_get_args()];
	}

	/**
	 * @dataProvider dataProvider
	 * @dataProvider dataProvider
	 */
	public function testMultipleDataProvider($a, $b)
	{
		$this->order[] = [__METHOD__, func_get_args()];
	}

	/** @dataProvider dataProviderIterator */
	public function testIteratorDataProvider($a, $b)
	{
		$this->order[] = [__METHOD__, func_get_args()];
	}

	/** @dataProvider ../Framework/fixtures/dataprovider.query.ini != foo */
	public function testFileDataProvider($a = 'a', $b = 'b')
	{
		$this->order[] = [__METHOD__, func_get_args()];
	}

	/** @dataProvider dataProvider */
	public function testAssertion()
	{
		Assert::true(FALSE);
	}
}


$test = new MyTest;
$test->run('testSingleDataProvider');
Assert::same([
	'MyTest::dataProvider',
	['MyTest::testSingleDataProvider', [1, 2]],
	['MyTest::testSingleDataProvider', [3, 4]],
], $test->order);


$test = new MyTest;
$test->run('testMultipleDataProvider');
Assert::same([
	'MyTest::dataProvider',
	'MyTest::dataProvider',
	['MyTest::testMultipleDataProvider', [1, 2]],
	['MyTest::testMultipleDataProvider', [3, 4]],
	['MyTest::testMultipleDataProvider', [1, 2]],
	['MyTest::testMultipleDataProvider', [3, 4]],
], $test->order);


$test = new MyTest;
$test->run('testIteratorDataProvider');
Assert::same([
	'MyTest::dataProviderIterator',
	['MyTest::testIteratorDataProvider', [1, 2]],
	['MyTest::testIteratorDataProvider', [3, 4]],
], $test->order);


$test = new MyTest;
$test->run('testFileDataProvider');
Assert::same([
	['MyTest::testFileDataProvider', ['1', 'b']],
	['MyTest::testFileDataProvider', ['a', '2']],
], $test->order);


Assert::exception(function () {
	$test = new MyTest;
	$test->run('testAssertion');
}, 'Tester\AssertException', 'FALSE should be TRUE in testAssertion(1, 2)');
