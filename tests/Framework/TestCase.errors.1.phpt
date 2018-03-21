<?php

/**
 * @exitCode 255
 * @outputMatch #^Test::setUp,E_USER_WARNING: setUp\s+in#
 */

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

Tester\Environment::$useColors = false;


class Test extends Tester\TestCase
{
	protected function setUp()
	{
		echo __METHOD__ . ',';
		trigger_error('setUp', E_USER_WARNING);
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
