<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::error(function () {
	$a++;
}, E_NOTICE);

Assert::error(function () {
	$a++;
}, 'E_NOTICE');

Assert::error(function () {
	$a++;
}, E_NOTICE, 'Undefined variable: a');

Assert::error(function () {
	$a++;
	@$x++;
	$b++;
}, [
	[E_NOTICE, 'Undefined variable: a'],
	[E_NOTICE, 'Undefined variable: b'],
]);

Assert::error(function () {
	$a++;
	$b++;
}, [
	[E_NOTICE],
	[E_NOTICE],
]);

Assert::error(function () {
	$a++;
	$b++;
}, [E_NOTICE, E_NOTICE]
);

Assert::exception(function () {
	Assert::error(function () {
	}, E_NOTICE);
}, Tester\AssertException::class, 'Error was expected, but was not generated');

Assert::exception(function () {
	Assert::error(function () {
		$a++;
	}, E_WARNING);
}, Tester\AssertException::class, 'E_WARNING was expected, but E_NOTICE (Undefined variable: a) was generated in file %a% on line %d%');

Assert::exception(function () {
	Assert::error(function () {
		$a++;
	}, E_NOTICE, 'Abc');
}, Tester\AssertException::class, "E_NOTICE with a message matching 'Abc' was expected but got 'Undefined variable: a'");

Assert::exception(function () {
	Assert::error(function () {
		$a++;
		$b++;
	}, E_NOTICE, 'Undefined variable: a');
}, Tester\AssertException::class, 'Generated more errors than expected: E_NOTICE (Undefined variable: b) was generated in file %a% on line %d%');

Assert::exception(function () {
	Assert::error(function () {
		$a++;
	}, [
		[E_NOTICE, 'Undefined variable: a'],
		[E_NOTICE, 'Undefined variable: b'],
	]);
}, Tester\AssertException::class, 'Error was expected, but was not generated');



$e = Assert::error(function () {
	throw new Exception;
}, Exception::class);

Assert::true($e instanceof Exception);

Assert::error(function () {
	throw new Exception('Text 123');
}, Exception::class, 'Text %d%');


Assert::exception(function () {
	Assert::error(function () {}, null);
}, Exception::class, 'Error type must be E_* constant.');
