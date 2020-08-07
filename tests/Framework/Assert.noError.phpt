<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::noError(function () {
	// no error there
});

Assert::exception(function () {
	Assert::noError(function () {
		$a = &pi();
	});
}, Tester\AssertException::class, 'Generated more errors than expected: E_NOTICE %a%');


Assert::exception(function () {
	Assert::noError(function () {
		throw new \Exception('Unexpected');
	});
}, Exception::class, 'Unexpected');


Assert::exception(function () {
	Assert::noError(function () {
		throw new \Exception('Unexpected');
	}, InvalidArgumentException::class);
}, Exception::class, 'Tester\Assert::noError() expects 1 parameter, 2 given.');
