<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::true(true);

Assert::exception(function(){
	Assert::true(false);
}, 'Tester\AssertException', 'Failed asserting that FALSE is TRUE');

Assert::exception(function(){
	Assert::true(1);
}, 'Tester\AssertException', 'Failed asserting that 1 is TRUE');

Assert::exception(function(){
	Assert::true(null);
}, 'Tester\AssertException', 'Failed asserting that NULL is TRUE');
