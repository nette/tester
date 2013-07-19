<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::error(function() {
	$a++;
}, E_NOTICE);

Assert::error(function() {
	$a++;
}, 'E_NOTICE');

Assert::error(function() {
	$a++;
}, E_NOTICE, 'Undefined variable: %a%');

Assert::exception(function() {
	Assert::error(function() {
	}, E_NOTICE);
}, 'Tester\AssertException', 'E_NOTICE was expected, but none was generated');

Assert::exception(function() {
	Assert::error(function() {
		$a++;
	}, E_WARNING);
}, 'Tester\AssertException', 'E_WARNING was expected, but E_NOTICE (Undefined variable: a) was generated in file %a% on line %d%');

Assert::exception(function() {
	Assert::error(function() {
		$a++;
	}, E_NOTICE, 'Abc');
}, 'Tester\AssertException', "E_NOTICE with a message matching %2 was expected but got %1");

Assert::exception(function() {
	Assert::error(function() {
		$a++;
		$b++;
	}, E_NOTICE, 'Undefined variable: a');
}, 'Tester\AssertException', 'Expected one E_NOTICE, but another E_NOTICE (Undefined variable: b) was generated in file %a% on line %d%');



$e = Assert::error(function() {
	throw new Exception;
}, 'Exception');

Assert::true( $e instanceof Exception );

Assert::error(function() {
	throw new Exception('Text 123');
}, 'Exception', 'Text %d%');


Assert::exception(function() {
	Assert::error(function() {}, NULL);
}, 'Exception', 'Error type must be E_* constant or Exception class name.');
