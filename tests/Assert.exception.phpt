<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::exception(function(){
	throw new Exception;
}, 'Exception');

Assert::exception(function(){
	throw new Exception('Text');
}, 'Exception');

Assert::exception(function(){
	throw new Exception('Text 123');
}, 'Exception', 'Text %d%');

Assert::exception(function(){
	Assert::exception(function(){
	}, 'Exception');
}, 'Tester\AssertException', 'Expected exception');

Assert::exception(function(){
	Assert::exception(function(){
		throw new Exception;
	}, 'Unknown');
}, 'Tester\AssertException', 'Failed asserting that Exception is an instance of class Unknown');

Assert::exception(function(){
	Assert::exception(function(){
		throw new Exception('Text');
	}, 'Exception', 'Abc');
}, 'Tester\AssertException', 'Failed asserting that "Text" matches expected "Abc"');
