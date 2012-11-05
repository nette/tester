<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::false(false);

Assert::exception(function(){
	Assert::false(true);
}, 'Tester\AssertException', 'Failed asserting that TRUE is FALSE');

Assert::exception(function(){
	Assert::false(0);
}, 'Tester\AssertException', 'Failed asserting that 0 is FALSE');

Assert::exception(function(){
	Assert::false(null);
}, 'Tester\AssertException', 'Failed asserting that NULL is FALSE');
