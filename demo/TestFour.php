<?php

declare(strict_types=1);


class TestFour extends Tester\TestCase
{
	public function testMe()
	{
		Tester\Assert::true(true);
		echo __FUNCTION__ . ',';
	}


	public function testMe2()
	{
		echo __FUNCTION__ . ',';
	}
}
