<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::type('\stdClass', new stdClass);

Assert::exception(function(){
	Assert::type('x', new stdClass);
}, 'Tester\AssertException', 'Failed asserting that stdClass(0) is instance of x.');
