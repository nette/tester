<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::false(FALSE);

$notFalse = array(TRUE, 0, 1, NULL, 'FALSE');

foreach ($notFalse as $value) {
	Assert::exception(function () use ($value) {
		Assert::false($value);
	}, 'Tester\AssertException', '%a% should be FALSE');
}

Assert::exception(function () {
	Assert::false(true, 'Custom description');
}, 'Tester\AssertException', 'Custom description: %a% should be FALSE');
