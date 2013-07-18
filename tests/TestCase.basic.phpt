<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class TestCaseTest extends Tester\TestCase
{
	public function testAssertion()
	{
		Assert::true(FALSE);
	}
}


Assert::exception(function() {
	$test = new TestCaseTest;
	$test->run('testAssertion');
}, 'Tester\AssertException', 'FALSE should be TRUE in testAssertion()');
