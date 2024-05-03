<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Dumper;
use Tester\Environment;


/**
 * Executes a provided test closure, handling setup and teardown operations.
 */
function test(string $description, Closure $closure): void
{
	if (($count = func_num_args()) > 2) {
		throw new Exception(__FUNCTION__ . "() expects 2 parameters, $count given.");
	}

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


/**
 * Tests for exceptions thrown by a provided closure matching specific criteria.
 */
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


/**
 * Registers a function to be called before each test execution.
 */
function setUp(?Closure $closure): void
{
	static $fn;
	$fn = $closure;
}


/**
 * Registers a function to be called after each test execution.
 */
function tearDown(?Closure $closure): void
{
	static $fn;
	$fn = $closure;
}
