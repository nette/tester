<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::null(null);

Assert::exception(function(){
	Assert::null(false);
}, 'Tester\AssertException', 'FALSE should be NULL');

Assert::exception(function(){
	Assert::null(0);
}, 'Tester\AssertException', '0 should be NULL');
