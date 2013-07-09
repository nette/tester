<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class CaughtCase extends Tester\TestCase
{
	public function testThrown()
	{
		$this->setExpectedException('Exception');
		throw new \Exception();
	}
}

$test = new CaughtCase;
$test->run();



class UncaughtCase extends Tester\TestCase
{
	public function testUncaught()
	{
		throw new \Exception();
	}
}

Assert::exception(function(){
	$test = new UncaughtCase;
	$test->run();
}, 'Exception', '');



class WrongCase extends Tester\TestCase
{
	public function testThrown()
	{
		$this->setExpectedException('RuntimeException');
		throw new \Exception;
	}
}

Assert::exception(function(){
	$test = new WrongCase;
	$test->run();
}, 'Tester\AssertException', 'Failed asserting that Exception is an instance of class RuntimeException');



class UnthrownCase extends Tester\TestCase
{
	public function testUnthrown()
	{
		$this->setExpectedException('Exception');
	}
}

Assert::exception(function(){
	$test = new UnthrownCase;
	$test->run();
}, 'Tester\AssertException', 'Expected exception Exception');



class NothingCase extends Tester\TestCase
{
	public function testNothing()
	{
	}
}

$test = new NothingCase;
$test->run();
