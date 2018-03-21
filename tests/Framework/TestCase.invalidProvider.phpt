<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class InvalidProviderTest extends Tester\TestCase
{
	public function invalidDataProvider()
	{
	}


	/** @dataProvider invalidDataProvider */
	public function testEmptyProvider()
	{
	}


	public function testMissingDataProvider($a)
	{
	}
}


Assert::exception(function () {
	$test = new InvalidProviderTest;
	$test->runTest('testEmptyProvider');
}, Tester\TestCaseException::class, "Data provider invalidDataProvider() doesn't return array or Traversable.");

Assert::exception(function () {
	$test = new InvalidProviderTest;
	$test->runTest('testMissingDataProvider');
}, Tester\TestCaseException::class, 'Method testMissingDataProvider() has arguments, but @dataProvider is missing.');
