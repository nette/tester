<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class MyException extends Exception
{
}

class MyTest extends Tester\TestCase
{
	/** @throws Exception */
	public function testThrows()
	{
		throw new Exception;
	}

	/** @throws Exception */
	public function testThrowsButDont()
	{
	}

	/** @throws Exception  With message */
	public function testThrowsMessage()
	{
		throw new Exception('With message');
	}

	/** @throws Exception */
	public function testFailAssertPass()
	{
		Assert::fail('failed');
	}

	/** @throws MyException */
	public function testThrowsBadClass()
	{
		throw new Exception;
	}

	/** @throws Exception  With message */
	public function testThrowsBadMessage()
	{
		throw new Exception('Bad message');
	}

	// Without @throws
	public function testWithoutThrows()
	{
		throw new Exception;
	}
	
	public function dataProvider()
	{
		return array(array(1));
	}
	
	/**
	 * @dataprovider dataProvider
	 * @throws Exception	 
	 */	 	
	public function testThrowsWithDataprovider($x)
	{
	}
}


$test = new MyTest;
$test->run('testThrows');
$test->run('testThrowsMessage');

Assert::exception(function() use ($test) {
	$test->run('testThrowsButDont');
}, 'Tester\AssertException', 'Expected exception Exception in MyTest::testThrowsButDont() method');

Assert::exception(function() use ($test) {
	$test->run('testFailAssertPass');
}, 'Tester\AssertException', 'failed');

Assert::exception(function() use ($test) {
	$test->run('testThrowsBadClass');
}, 'Tester\AssertException', 'Failed asserting that Exception is an instance of class MyException in MyTest::testThrowsBadClass() method');

Assert::exception(function() use ($test) {
	$test->run('testThrowsBadMessage');
}, 'Tester\AssertException', 'Failed asserting that "Bad message" matches expected "With message" in MyTest::testThrowsBadMessage() method');

Assert::exception(function() use ($test) {
	$test->run('testWithoutThrows');
}, 'Exception');

Assert::exception(function() use ($test) {
	$test->run('testThrowsWithDataprovider');
}, 'Exception', 'Expected exception Exception in MyTest::testThrowsWithDataprovider() method (dataprovider #0)');



Assert::exception(function() use ($test) {
	$test->run('testUndefinedMethod');
}, 'ReflectionException', 'Method testUndefinedMethod does not exist');
