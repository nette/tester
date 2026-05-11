<?php declare(strict_types=1);

/**
 * @outputMatch Test::setUp,Test::testMe,Test::tearDown√ testMe
 */

require __DIR__ . '/../bootstrap.php';

Tester\Environment::$useColors = false;


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
	}


	protected function tearDown()
	{
		echo __METHOD__;
	}


	protected function data()
	{
		return [['arg']];
	}
}

(new Test)->run();
