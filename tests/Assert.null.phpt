<?php

require __DIR__ . '/bootstrap.php';


Assert::null(null);

Assert::exception(function(){
	Assert::null(false);
}, 'AssertException', 'Failed asserting that FALSE is NULL');

Assert::exception(function(){
	Assert::null(0);
}, 'AssertException', 'Failed asserting that 0 is NULL');
