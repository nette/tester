<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class Test extends Tester\TestCase
{
	protected function tearDown()
	{
	}

	public function testMe()
	{
		Assert::true(TRUE);
	}
}

Assert::exception(function () {
	(new Test)->run();
}, 'Tester\TestCaseException', "Test doesn't call parents tearDown() method.");
