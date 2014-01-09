<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class MyTest extends Tester\TestCase
{
	protected function testProtected()
	{
	}

	private function testPrivate()
	{
	}

}


$test = new MyTest;

Assert::exception(function() use ($test) {
	$test->run('testProtected');
}, 'Tester\TestCaseException', 'Method testProtected is not public. Make it public or rename it.');

Assert::exception(function() use ($test) {
	$test->run('testPrivate');
}, 'Tester\TestCaseException', 'Method testPrivate is not public. Make it public or rename it.');
