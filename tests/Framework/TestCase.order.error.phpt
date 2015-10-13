<?php

/**
 * @exitCode 255
 * @outputMatch Test::setUp,Test::testMe,Test::tearDown,E_USER_ERROR: STOP%A%
 */

require __DIR__ . '/../bootstrap.php';

Tester\Environment::$useColors = FALSE;


class Test extends Tester\TestCase
{
	protected function setUp()
	{
		echo __METHOD__ . ',';
	}

	/** @dataProvider data */
	public function testMe($arg)
	{
		echo __METHOD__ . ',';
		trigger_error('STOP', E_USER_ERROR);
	}

	protected function tearDown()
	{
		echo __METHOD__ . ',';
		trigger_error('NOT SHOWN', E_USER_ERROR);
	}

	protected function data()
	{
		return array(array('arg'));
	}
}


$test = new Test;
$test->run();
