<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Dumper;
use Tester\Environment;


function test(string $description, Closure $closure): void
{
	if ($fn = (new ReflectionFunction('setUp'))->getStaticVariables()['fn']) {
		$fn();
	}

	try {
		$closure();
		if ($description !== '') {
			Environment::print(Dumper::color('lime', '√') . " $description");
		}

	} catch (Throwable $e) {
		if ($description !== '') {
			Environment::print(Dumper::color('red', '×') . " $description\n\n");
		}
		throw $e;
	}

	if ($fn = (new ReflectionFunction('tearDown'))->getStaticVariables()['fn']) {
		$fn();
	}
}


function testException(
	string $description,
	Closure $function,
	string $class,
	?string $message = null,
	$code = null,
): void
{
	try {
		Assert::exception($function, $class, $message, $code);
		if ($description !== '') {
			Environment::print(Dumper::color('lime', '√') . " $description");
		}

	} catch (Throwable $e) {
		if ($description !== '') {
			Environment::print(Dumper::color('red', '×') . " $description\n\n");
		}
		throw $e;
	}
}


function setUp(?Closure $closure): void
{
	static $fn;
	$fn = $closure;
}


function tearDown(?Closure $closure): void
{
	static $fn;
	$fn = $closure;
}
