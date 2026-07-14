<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Attributes\Throws;

require __DIR__ . '/../bootstrap.php';


class MyException extends Exception
{
}

class MyTest extends Tester\TestCase
{
	#[Throws(Exception::class)]
	public function testThrows()
	{
		throw new Exception;
	}


	#[Throws(Exception::class)]
	public function testThrowsButDont()
	{
	}


	#[Throws(Exception::class, 'With message')]
	public function testThrowsMessage()
	{
		throw new Exception('With message');
	}


	#[Throws(Exception::class)]
	public function testFailAssertPass()
	{
		Assert::fail('failed');
	}


	#[Throws(MyException::class)]
	public function testThrowsBadClass()
	{
		throw new Exception;
	}


	#[Throws(Exception::class, 'With message')]
	public function testThrowsBadMessage()
	{
		throw new Exception('Bad message');
	}


	#[Throws(E_NOTICE)]
	public function testNotice()
	{
		$a = &pi();
	}


	#[Throws(E_NOTICE, 'Only variables should be assigned by reference')]
	public function testNoticeMessage()
	{
		$a = &pi();
	}


	#[Throws(E_WARNING)]
	public function testBadError()
	{
		$a = &pi();
	}


	#[Throws(E_NOTICE, 'With message')]
	public function testNoticeBadMessage()
	{
		$a = &pi();
	}


	public function dataProvider()
	{
		return [[1]];
	}


	#[Tester\Attributes\DataProvider('dataProvider')]
	#[Throws(Exception::class)]
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
	fn() => $test->runTest('testThrowsWithDataprovider'),
	Exception::class,
	"Exception was expected, but none was thrown in testThrowsWithDataprovider(1) (data set '0')",
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
