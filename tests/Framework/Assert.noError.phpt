<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::noError(function () {
	// no error there
});

Assert::exception(function () {
	Assert::noError(function () {
		$a++;
	});
}, 'Tester\AssertException', 'Generated more errors than expected: E_NOTICE %a%');


Assert::exception(function () {
	Assert::noError(function () {
		throw new \Exception('Unexpected');
	});
}, 'Exception', 'Unexpected');
