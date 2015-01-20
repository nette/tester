<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


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
}, 'Tester\AssertException', "Exception with a message matching 'Abc' was expected but got 'Text'");

Assert::exception(function() {
	throw new Exception('Text', 42);
}, 'Exception', NULL, 42);

Assert::exception(function() {
	Assert::exception(function() {
		throw new Exception('Text', 1);
	}, 'Exception', NULL, 42);
}, 'Tester\AssertException', 'Exception with a code 42 was expected but got 1');

$old = Assert::$onFailure;
Assert::$onFailure = function() {};
$e = Assert::exception(function() {}, 'Exception');
Assert::$onFailure = $old;
Assert::null($e);
