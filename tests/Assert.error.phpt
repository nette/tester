<?php

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
}, 'AssertException', 'Expected error');

Assert::exception(function(){
	Assert::error(function(){
		$a++;
	}, E_WARNING);
}, 'AssertException', 'Failed asserting that E_NOTICE is E_WARNING');

Assert::exception(function(){
	Assert::error(function(){
		$a++;
	}, E_NOTICE, 'Abc');
}, 'AssertException', 'Failed asserting that "Undefined variable: a" matches expected "Abc"');
