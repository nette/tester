<?php

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
	$test->run('testEmptyProvider');
}, 'Tester\TestCaseException', "Data provider invalidDataProvider() doesn't return array.");

Assert::exception(function () {
	$test = new InvalidProviderTest;
	$test->run('testMissingDataProvider');
}, 'Tester\TestCaseException', 'Method testMissingDataProvider() has arguments, but @dataProvider is missing.');
