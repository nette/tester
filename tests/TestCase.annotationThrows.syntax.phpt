<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

class MyTest extends Tester\TestCase
{
	/**
	 * @throws
	 */
	public function testThrowsNoClass()
	{
	}

	/**
	 * @throws Exception
	 * @throws Exception
	 */
	public function testThrowsMultiple()
	{
	}

}

$test = new MyTest;

Assert::exception(function() use ($test) {
	$test->run('testThrowsNoClass');
}, 'Tester\TestCaseException', 'Missing class name in @throws annotation.');

$e = Assert::exception(function() use ($test) {
	$test->run('testThrowsMultiple');
}, 'Tester\TestCaseException', 'Cannot specify @throws annotation more then once.');

$rm = new ReflectionMethod($test, 'testThrowsMultiple');
$trace = $e->getTrace();
Assert::same(array(
	'file' => __FILE__,
	'line' => $rm->getStartLine(),
	'function' => 'testThrowsMultiple',
	'class' => 'MyTest',
	'type' => '->',
	'args' => array(),
), end($trace));
