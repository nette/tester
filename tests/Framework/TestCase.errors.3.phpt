<?php

/**
 * @exitCode 255
 * @outputMatch #^Test::setUp,Test::testMe,Test::tearDown,E_USER_WARNING: tearDown\s+in#
 */

declare(strict_types=1);

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
		trigger_error('tearDown', E_USER_WARNING);
	}
}

(new Test)->run();
