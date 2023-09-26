<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::nan(NAN);

$notNan = [false, 0, null, '', 'NAN'];

foreach ($notNan as $value) {
	Assert::exception(
		fn() => Assert::nan($value),
		Tester\AssertException::class,
		'%a% should be NAN',
	);
}

Assert::exception(
	fn() => Assert::nan(1, 'Custom description'),
	Tester\AssertException::class,
	'Custom description: %a% should be NAN',
);
