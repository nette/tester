<?php

/**
 * TEST: Prevent loop in error handling. The #268 regression.
 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

class Test extends Tester\TestCase
{
	/** @dataProvider data */
	public function testMe($arg)
	{
		if ($arg === 1) {
			set_error_handler(function() {});
		} else {
			@trigger_error('MUTED', E_USER_WARNING);
			Assert::true(TRUE);
		}
	}

	protected function data()
	{
		return array(array(1), array(2));
	}
}


$test = new Test;
$test->run();
