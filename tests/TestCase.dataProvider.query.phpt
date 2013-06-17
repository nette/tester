<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class MyTest extends Tester\TestCase
{
	public $order;

	/** @dataProvider fixtures/dataprovider.query.ini != foo */
	public function testFileDataProvider($data)
	{
		$this->order[] = array(__METHOD__, func_get_args());
	}

}


$test = new MyTest;
$test->run();

Assert::same(array(
	array('MyTest::testFileDataProvider', array('1')),
	array('MyTest::testFileDataProvider', array('2')),
), $test->order);
