<?php declare(strict_types=1);

use Tester\Ansi;
use Tester\Assert;
use Tester\Environment;


/**
 * Runs a labeled test closure, calling setUp and tearDown around it.
 * @param \Closure(): mixed  $closure
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
			Environment::print(Ansi::colorize('√', 'lime') . " $description");
		}

	} catch (Throwable $e) {
		if ($description !== '') {
			Environment::print(Ansi::colorize('×', 'red') . " $description\n\n");
		}
		throw $e;

	} finally {
		if ($fn = (new ReflectionFunction('tearDown'))->getStaticVariables()['fn']) {
			$fn();
		}
	}
}


/**
 * Runs a labeled test that asserts the closure throws a specific exception.
 * @param \Closure(): void  $function
 * @param class-string<\Throwable>  $class
 */
function testException(
	string $description,
	Closure $function,
	string $class,
	?string $message = null,
	int|string|null $code = null,
): void
{
	test($description, fn() => Assert::exception($function, $class, $message, $code));
}


/**
 * Runs a labeled test that asserts the closure generates no errors or exceptions.
 * @param \Closure(): void  $function
 */
function testNoError(string $description, Closure $function): void
{
	if (($count = func_num_args()) > 2) {
		throw new Exception(__FUNCTION__ . "() expects 2 parameters, $count given.");
	}

	test($description, fn() => Assert::noError($function));
}


/**
 * Sets the closure to call before each test() invocation.
 * @param (\Closure(): void)|null  $closure
 */
function setUp(?Closure $closure): void
{
	static $fn;
	$fn = $closure;
}


/**
 * Sets the closure to call after each test() invocation.
 * @param (\Closure(): void)|null  $closure
 */
function tearDown(?Closure $closure): void
{
	static $fn;
	$fn = $closure;
}
