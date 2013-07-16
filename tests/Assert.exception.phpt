<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$e = Assert::exception(function(){
	throw new Exception;
}, 'Exception');

Assert::true( $e instanceof Exception );

Assert::exception(function(){
	throw new Exception('Text');
}, 'Exception');

Assert::exception(function(){
	throw new Exception('Text 123');
}, 'Exception', 'Text %d%');

Assert::exception(function(){
	Assert::exception(function(){
	}, 'Exception');
}, 'Tester\AssertException', 'Expected Exception but no exception was thrown.');

Assert::exception(function(){
	Assert::exception(function(){
		throw new Exception;
	}, 'Unknown');
}, 'Tester\AssertException', "Expected Unknown but Exception with message '' was thrown.");

Assert::exception(function(){
	Assert::exception(function(){
		throw new Exception('Text');
	}, 'Exception', 'Abc');
}, 'Tester\AssertException', 'Exception message "Text" not matches expected "Abc"');
