<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::nan(NAN);

$notNan = array(FALSE, 0, NULL, '', 'NAN');

foreach ($notNan as $value) {
	Assert::exception(function () use ($value) {
		Assert::nan($value);
	}, 'Tester\AssertException', '%a% should be NAN');
}

Assert::exception(function () {
	Assert::nan(1, 'Custom description');
}, 'Tester\AssertException', 'Custom description: %a% should be NAN');
