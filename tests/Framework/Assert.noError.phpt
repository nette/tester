<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

class MyException extends Exception {}

Assert::noError(function () {

}, 'Assertion does not fail if no error/exception occured');

Assert::exception(function () {
	Assert::noError(function () {
		$a++;
	}, 'Incrementing undefined variable');
}, 'Tester\AssertException', 'Incrementing undefined variable: occurred error E_NOTICE (Undefined variable: a)');

Assert::noError(function () {
	@$a++;
}, 'Suppressed error should pass');

Assert::exception(function () {
	Assert::noError(function () {
		throw new MyException('some message', -123);
	}, 'Throwing an exception');
}, 'Tester\AssertException', 'Throwing an exception: MyException was thrown. Code: -123 Message: some message');
