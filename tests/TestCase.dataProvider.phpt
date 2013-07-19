<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class MyTest extends Tester\TestCase
{
	public $order;

	public function dataProvider()
	{
		$this->order[] = __METHOD__;
		return array(
			array(1, 2),
			array(3, 4),
		);
	}

	/** @dataProvider dataProvider*/
	public function testSingleDataProvider($a, $b)
	{
		$this->order[] = array(__METHOD__, func_get_args());
	}

	/**
	 * @dataProvider dataProvider
	 * @dataProvider dataProvider
	*/
	public function testMultipleDataProvider($a, $b)
	{
		$this->order[] = array(__METHOD__, func_get_args());
	}

	/** @dataProvider fixtures/dataprovider.query.ini != foo */
	public function testFileDataProvider($data)
	{
		$this->order[] = array(__METHOD__, func_get_args());
	}

	/** @dataProvider dataProvider*/
	public function testAssertion()
	{
		Assert::true(FALSE);
	}
}


$test = new MyTest;
$test->run('testSingleDataProvider');
Assert::same(array(
	'MyTest::dataProvider',
	array('MyTest::testSingleDataProvider', array(1, 2)),
	array('MyTest::testSingleDataProvider', array(3, 4)),
), $test->order);


$test = new MyTest;
$test->run('testMultipleDataProvider');
Assert::same(array(
	'MyTest::dataProvider',
	'MyTest::dataProvider',
	array('MyTest::testMultipleDataProvider', array(1, 2)),
	array('MyTest::testMultipleDataProvider', array(3, 4)),
	array('MyTest::testMultipleDataProvider', array(1, 2)),
	array('MyTest::testMultipleDataProvider', array(3, 4)),
), $test->order);


$test = new MyTest;
$test->run('testFileDataProvider');
Assert::same(array(
	array('MyTest::testFileDataProvider', array('1')),
	array('MyTest::testFileDataProvider', array('2')),
), $test->order);


Assert::exception(function() {
	$test = new MyTest;
	$test->run('testAssertion');
}, 'Tester\AssertException', '%1 should be TRUE in testAssertion(1, 2)');
