<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::false(false);

Assert::exception(function(){
	Assert::false(true);
}, 'Tester\AssertException', 'TRUE should be FALSE');

Assert::exception(function(){
	Assert::false(0);
}, 'Tester\AssertException', '0 should be FALSE');

Assert::exception(function(){
	Assert::false(null);
}, 'Tester\AssertException', 'NULL should be FALSE');
