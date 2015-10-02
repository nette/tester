<?php

/**
 * @exitCode 255
 * @outputMatch Test::setUp,Test::testMe,Test::tearDown,E_USER_WARNING: SHOWN%A%
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
		@trigger_error('MUTED', E_USER_WARNING);
		trigger_error('SHOWN', E_USER_WARNING);
		trigger_error('AFTER', E_USER_WARNING);
	}

	protected function tearDown()
	{
		echo __METHOD__ . ',';
		trigger_error('NOT SHOWN', E_USER_WARNING);
	}

	protected function data()
	{
		return [['arg']];
	}
}


$test = new Test;
$test->run();
