<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class InvalidDataProviderTest extends Tester\TestCase
{
	public function invalidDataProvider()
	{
	}

	/** @dataProvider invalidDataProvider */
	public function testDataProvider()
	{
	}

}


Assert::exception(function() {
	$test = new InvalidDataProviderTest;
	$test->run();
}, 'Tester\TestCaseException', "Data provider invalidDataProvider() doesn't return array.");
