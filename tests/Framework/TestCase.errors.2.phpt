<?php

/**
 * @exitCode 255
 * @outputMatch #^Test::setUp,Test::testMe,Test::tearDown,E_USER_WARNING: testMe\s+in#
 */

require __DIR__ . '/../bootstrap.php';

Tester\Environment::$useColors = false;


class Test extends Tester\TestCase
{
	protected function setUp()
	{
		echo __METHOD__ . ',';
	}


	public function testMe()
	{
		echo __METHOD__ . ',';
		trigger_error('testMe', E_USER_WARNING);
	}


	protected function tearDown()
	{
		echo __METHOD__ . ',';
		trigger_error('tearDown', E_USER_WARNING);
	}
}

(new Test)->run();
