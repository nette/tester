<?php

declare(strict_types=1);


class TestTwo extends Tester\TestCase
{
	public function testMe()
	{
		Tester\Assert::true(true);
		echo __FUNCTION__ . ',';
	}
}
