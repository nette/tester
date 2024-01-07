<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


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


	/** @throws E_NOTICE */
	public function testNotice()
	{
		$a = &pi();
	}


	/** @throws E_NOTICE  Only variables should be assigned by reference */
	public function testNoticeMessage()
	{
		$a = &pi();
	}


	/** @throws E_WARNING */
	public function testBadError()
	{
		$a = &pi();
	}


	/** @throws E_NOTICE  With message */
	public function testNoticeBadMessage()
	{
		$a = &pi();
	}


	// Without @throws
	public function testWithoutThrows()
	{
		throw new Exception;
	}


	public function dataProvider()
	{
		return [[1]];
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
$test->runTest('testThrows');
$test->runTest('testThrowsMessage');

Assert::exception(
	fn() => $test->runTest('testThrowsButDont'),
	Tester\AssertException::class,
	'Exception was expected, but none was thrown in testThrowsButDont()',
);

Assert::exception(
	fn() => $test->runTest('testFailAssertPass'),
	Tester\AssertException::class,
	'failed in testFailAssertPass()',
);

Assert::exception(
	fn() => $test->runTest('testThrowsBadClass'),
	Tester\AssertException::class,
	'MyException was expected but got Exception in testThrowsBadClass()',
);

Assert::exception(
	fn() => $test->runTest('testThrowsBadMessage'),
	Tester\AssertException::class,
	"Exception with a message matching 'With message' was expected but got 'Bad message' in testThrowsBadMessage()",
);

Assert::exception(
	fn() => $test->runTest('testWithoutThrows'),
	Exception::class,
);

Assert::exception(
	fn() => $test->runTest('testThrowsWithDataprovider'),
	Exception::class,
	"Exception was expected, but none was thrown in testThrowsWithDataprovider(1) (data set '0')",
);

Assert::exception(
	fn() => $test->runTest('testUndefinedMethod'),
	Tester\TestCaseException::class,
	"Method 'testUndefinedMethod' does not exist.",
);

$test->runTest('testNotice');
$test->runTest('testNoticeMessage');

Assert::exception(
	fn() => $test->runTest('testBadError'),
	Tester\AssertException::class,
	'E_WARNING was expected, but E_NOTICE (Only variables should be assigned by reference) was generated in %a%testBadError()',
);

Assert::exception(
	fn() => $test->runTest('testNoticeBadMessage'),
	Tester\AssertException::class,
	"E_NOTICE with a message matching 'With message' was expected but got 'Only variables should be assigned by reference' in testNoticeBadMessage()",
);
