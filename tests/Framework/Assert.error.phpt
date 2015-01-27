<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::error(function() {
	$a++;
}, E_NOTICE);

Assert::error(function() {
	$a++;
}, 'E_NOTICE');

Assert::error(function() {
	$a++;
}, E_NOTICE, 'Undefined variable: a');

Assert::error(function() {
	$a++;
	@$x++;
	$b++;
}, array(
	array(E_NOTICE, 'Undefined variable: a'),
	array(E_NOTICE, 'Undefined variable: b'),
));

Assert::exception(function() {
	Assert::error(function() {
	}, E_NOTICE);
}, 'Tester\AssertException', 'Error was expected, but was not generated');

Assert::exception(function() {
	Assert::error(function() {
		$a++;
	}, E_WARNING);
}, 'Tester\AssertException', 'E_WARNING was expected, but E_NOTICE (Undefined variable: a) was generated in file %a% on line %d%');

Assert::exception(function() {
	Assert::error(function() {
		$a++;
	}, E_NOTICE, 'Abc');
}, 'Tester\AssertException', "E_NOTICE with a message matching 'Abc' was expected but got 'Undefined variable: a'");

Assert::exception(function() {
	Assert::error(function() {
		$a++;
		$b++;
	}, E_NOTICE, 'Undefined variable: a');
}, 'Tester\AssertException', 'Generated more errors than expected: E_NOTICE (Undefined variable: b) was generated in file %a% on line %d%');

Assert::exception(function() {
	Assert::error(function() {
		$a++;
	}, array(
		array(E_NOTICE, 'Undefined variable: a'),
		array(E_NOTICE, 'Undefined variable: b'),
	));
}, 'Tester\AssertException', 'Error was expected, but was not generated');



$e = Assert::error(function() {
	throw new Exception;
}, 'Exception');

Assert::true( $e instanceof Exception );

Assert::error(function() {
	throw new Exception('Text 123');
}, 'Exception', 'Text %d%');


Assert::exception(function() {
	Assert::error(function() {}, NULL);
}, 'Exception', 'Error type must be E_* constant.');
