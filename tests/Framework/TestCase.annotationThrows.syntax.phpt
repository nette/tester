<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class MyTest extends Tester\TestCase
{
	/**
	 * @throws
	 */
	public function testThrowsNoClass()
	{
	}


	/**
	 * @throws Exception
	 * @throws Exception
	 */
	public function testThrowsMultiple()
	{
	}
}

$test = new MyTest;

Assert::exception(function () use ($test) {
	$test->runTest('testThrowsNoClass');
}, Tester\TestCaseException::class, 'Missing class name in @throws annotation for testThrowsNoClass().');

Assert::exception(function () use ($test) {
	$test->runTest('testThrowsMultiple');
}, Tester\TestCaseException::class, 'Annotation @throws for testThrowsMultiple() can be specified only once.');
