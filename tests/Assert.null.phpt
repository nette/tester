<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::null(null);

Assert::exception(function(){
	Assert::null(false);
}, 'Tester\AssertException', 'Failed asserting that FALSE is NULL');

Assert::exception(function(){
	Assert::null(0);
}, 'Tester\AssertException', 'Failed asserting that 0 is NULL');
