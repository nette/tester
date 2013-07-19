<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$e = Assert::exception(function() {
	throw new Exception;
}, 'Exception');

Assert::true( $e instanceof Exception );

Assert::exception(function() {
	throw new Exception('Text 123');
}, 'Exception', 'Text %d%');

Assert::exception(function() {
	Assert::exception(function() {
	}, 'Exception');
}, 'Tester\AssertException', 'Exception was expected, but none was thrown');

Assert::exception(function() {
	Assert::exception(function() {
		throw new Exception('message');
	}, 'UnknownException');
}, 'Tester\AssertException', 'UnknownException was expected but got Exception (message)');

Assert::exception(function() {
	Assert::exception(function() {
		throw new Exception('Text');
	}, 'Exception', 'Abc');
}, 'Tester\AssertException', "Exception with a message matching %2 was expected but got %1");
