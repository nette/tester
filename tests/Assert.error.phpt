<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::error(function(){
	$a++;
}, E_NOTICE);

Assert::error(function(){
	$a++;
}, E_NOTICE, 'Undefined variable: %a%');

Assert::exception(function(){
	Assert::error(function(){
	}, E_NOTICE);
}, 'Tester\AssertException', 'Expected E_NOTICE but no error was generated.');

Assert::exception(function(){
	Assert::error(function(){
		$a++;
	}, E_WARNING);
}, 'Tester\AssertException', 'Expected E_WARNING but E_NOTICE (Undefined variable: a in file %a% on line %d%) was generated.');

Assert::exception(function(){
	Assert::error(function(){
		$a++;
	}, E_NOTICE, 'Abc');
}, 'Tester\AssertException', 'Error message "Undefined variable: a" not matches expected "Abc"');

Assert::exception(function(){
	Assert::error(function(){
		$a++;
		$b++;
	}, E_NOTICE, 'Undefined variable: a');
}, 'Tester\AssertException', 'Expected E_NOTICE, got it, but another E_NOTICE (Undefined variable: b in file %a% on line %d%) was generated.');
