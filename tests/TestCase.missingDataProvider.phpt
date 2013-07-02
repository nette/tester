<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class MissingDataProviderTest extends Tester\TestCase
{

	public function testDataProvider($a)
	{
	}

}


Assert::exception(function(){
	$test = new MissingDataProviderTest;
	$test->run();
}, 'Tester\TestCaseException', "Method testDataProvider() has arguments, but @dataProvider is missing.");
