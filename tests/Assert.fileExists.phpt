<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::fileExists(__FILE__);

Assert::exception(function() {
	Assert::fileExists(__DIR__ . DIRECTORY_SEPARATOR . 'PHPUnit');
}, 'Tester\AssertException', 'File %a% does not exist');
