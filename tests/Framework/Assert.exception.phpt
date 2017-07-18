<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$e = Assert::exception(function () {
	throw new Exception;
}, 'Exception');

Assert::true($e instanceof Exception);

Assert::exception(function () {
	throw new Exception('Text 123');
}, 'Exception', 'Text %d%');

if (PHP_VERSION_ID >= 70000) {
	Assert::exception(function () {
		eval('*');
	}, 'Error', 'syntax error%a%');
}

Assert::exception(function () {
	Assert::exception(function () {
	}, 'Exception');
}, 'Tester\AssertException', 'Exception was expected, but none was thrown');

$e = Assert::exception(function () use (&$inner) {
	Assert::exception(function () use (&$inner) {
		throw $inner = new Exception('message');
	}, 'UnknownException');
}, 'Tester\AssertException', 'UnknownException was expected but got Exception (message)');
Assert::same($inner, $e->getPrevious());

$e = Assert::exception(function () {
	Assert::exception(function () {
		throw new Exception('Text');
	}, 'Exception', 'Abc');
}, 'Tester\AssertException', "Exception with a message matching 'Abc' was expected but got 'Text'");
Assert::null($e->getPrevious());

Assert::exception(function () {
	throw new Exception('Text', 42);
}, 'Exception', null, 42);

$e = Assert::exception(function () {
	Assert::exception(function () {
		throw new Exception('Text', 1);
	}, 'Exception', null, 42);
}, 'Tester\AssertException', 'Exception with a code 42 was expected but got 1');
Assert::null($e->getPrevious());

$old = Assert::$onFailure;
Assert::$onFailure = function () {};
$e = Assert::exception(function () {}, 'Exception');
Assert::$onFailure = $old;
Assert::null($e);
