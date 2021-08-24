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


	public function invalidDataProviderItem()
	{
		return ['non-array-item'];
	}


	/** @dataProvider invalidDataProviderItem */
	public function testInvalidDataProviderItem()
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

Assert::exception(function () {
	$test = new InvalidProviderTest;
	$test->runTest('testInvalidDataProviderItem');
}, Tester\TestCaseException::class, "Data provider invalidDataProviderItem() item '0' must be an array, string given.");
