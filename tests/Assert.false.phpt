<?php

require __DIR__ . '/bootstrap.php';


Assert::false(false);

Assert::exception(function(){
	Assert::false(true);
}, 'AssertException', 'Failed asserting that TRUE is FALSE');

Assert::exception(function(){
	Assert::false(0);
}, 'AssertException', 'Failed asserting that 0 is FALSE');

Assert::exception(function(){
	Assert::false(null);
}, 'AssertException', 'Failed asserting that NULL is FALSE');
