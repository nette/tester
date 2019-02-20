<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Expect;

require __DIR__ . '/../bootstrap.php';


// single expectation
$expectation = Expect::type('int');

Assert::same("type('int')", $expectation->dump());

Assert::exception(function () use ($expectation) {
	$expectation->__invoke('123');
}, Tester\AssertException::class, 'string should be int');

Assert::noError(function () use ($expectation) {
	$expectation->__invoke(123);
});


// expectation + expectation via and()
$expectation = Expect::type('string')->and(Expect::match('%d%'));

Assert::same("type('string'),match('%d%')", $expectation->dump());

Assert::exception(function () use ($expectation) {
	$expectation->__invoke(123);
}, Tester\AssertException::class, 'integer should be string');

Assert::noError(function () use ($expectation) {
	$expectation->__invoke('123');
});

Assert::exception(function () use ($expectation) {
	$expectation->__invoke('abc');
}, Tester\AssertException::class, "'abc' should match '%%d%%'");


// expectation + expectation via andMethod()
$expectation = Expect::type('string')->andMatch('%d%');

Assert::same("type('string'),match('%d%')", $expectation->dump());

Assert::exception(function () use ($expectation) {
	$expectation->__invoke(123);
}, Tester\AssertException::class, 'integer should be string');

Assert::noError(function () use ($expectation) {
	$expectation->__invoke('123');
});

Assert::exception(function () use ($expectation) {
	$expectation->__invoke('abc');
}, Tester\AssertException::class, "'abc' should match '%%d%%'");


// expectation + closure
$expectation = Expect::type('int')->and(function ($val) { return $val > 0; });

Assert::same("type('int'),user-expectation", $expectation->dump());

Assert::exception(function () use ($expectation) {
	$expectation->__invoke('123');
}, Tester\AssertException::class, 'string should be int');

Assert::noError(function () use ($expectation) {
	$expectation->__invoke(123);
});

Assert::exception(function () use ($expectation) {
	$expectation->__invoke(-123);
}, Tester\AssertException::class, "-123 is expected to be 'user-expectation'");


// callable + callable
class Test
{
	public function isOdd($val)
	{
		return (bool) ($val % 2);
	}
}

$expectation = Expect::that('is_int')
	->and([new Test, 'isOdd']);

Assert::same('is_int,user-expectation', $expectation->dump());

Assert::exception(function () use ($expectation) {
	$expectation->__invoke('123');
}, Tester\AssertException::class, "'123' is expected to be 'is_int'");

Assert::noError(function () use ($expectation) {
	$expectation->__invoke(123);
});

Assert::exception(function () use ($expectation) {
	$expectation->__invoke(124);
}, Tester\AssertException::class, "124 is expected to be 'user-expectation'");
