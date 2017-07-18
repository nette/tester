<?php

/**
 * @exitCode 255
 * @outputMatch #^Test::setUp,Test::testMe,Test::tearDown,Exception: tearDown\s+in#
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
	}


	protected function tearDown()
	{
		echo __METHOD__ . ',';
		throw new Exception('tearDown');
	}
}

(new Test)->run();
