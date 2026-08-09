<?php declare(strict_types=1);

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


	public function noDataSets()
	{
		return [];
	}


	public function noDataSetsGenerator()
	{
		yield from [];
	}


	/** @dataProvider noDataSets */
	public function testNoDataSets($a)
	{
	}


	/** @dataProvider noDataSetsGenerator */
	public function testNoDataSetsGenerator($a)
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
}, Tester\TestCaseException::class, 'Method testMissingDataProvider() has arguments, but no data provider is configured.');

Assert::exception(function () {
	$test = new InvalidProviderTest;
	$test->runTest('testInvalidDataProviderItem');
}, Tester\TestCaseException::class, "Data provider invalidDataProviderItem() item '0' must be an array, string given.");

Assert::exception(function () {
	$test = new InvalidProviderTest;
	$test->runTest('testNoDataSets');
}, Tester\TestCaseException::class, 'Data provider noDataSets() returned no data sets.');

Assert::exception(function () {
	$test = new InvalidProviderTest;
	$test->runTest('testNoDataSetsGenerator');
}, Tester\TestCaseException::class, 'Data provider noDataSetsGenerator() returned no data sets.');
