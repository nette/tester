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
}, 'Tester\AssertException', 'Exception was expected, but none was thrown in testThrowsButDont()');

Assert::exception(function() use ($test) {
	$test->run('testFailAssertPass');
}, 'Tester\AssertException', 'failed in testFailAssertPass()');

Assert::exception(function() use ($test) {
	$test->run('testThrowsBadClass');
}, 'Tester\AssertException', 'MyException was expected but got Exception in testThrowsBadClass()');

Assert::exception(function() use ($test) {
	$test->run('testThrowsBadMessage');
}, 'Tester\AssertException', "Exception with a message matching 'With message' was expected but got 'Bad message' in testThrowsBadMessage()");

Assert::exception(function() use ($test) {
	$test->run('testWithoutThrows');
}, 'Exception');

Assert::exception(function() use ($test) {
	$test->run('testThrowsWithDataprovider');
}, 'Exception', 'Exception was expected, but none was thrown in testThrowsWithDataprovider(1)');

Assert::exception(function() use ($test) {
	$test->run('testUndefinedMethod');
}, 'ReflectionException', 'Method testUndefinedMethod does not exist');
