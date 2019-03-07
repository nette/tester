<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';


class MyTest extends Tester\TestCase
{
	public function testMe1()
	{
		Tester\Assert::true(true);
		echo __FUNCTION__ . ',';
	}


	public function testMe2()
	{
		echo __FUNCTION__ . ',';
	}
}


(new Tester\TestCaseRunner)
	->run(new MyTest);

// this could be the recommended way to run single test, that will replace @testcase
