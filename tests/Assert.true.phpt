<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::true(true);

Assert::exception(function(){
	Assert::true(false);
}, 'Tester\AssertException', 'FALSE should be TRUE');

Assert::exception(function(){
	Assert::true(1);
}, 'Tester\AssertException', '1 should be TRUE');

Assert::exception(function(){
	Assert::true(null);
}, 'Tester\AssertException', 'NULL should be TRUE');
