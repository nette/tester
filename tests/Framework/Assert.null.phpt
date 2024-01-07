<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::null(null);

$notNull = [false, 0, '', 'null'];

foreach ($notNull as $value) {
	Assert::notNull($notNull);
	Assert::exception(
		fn() => Assert::null($value),
		Tester\AssertException::class,
		'%a% should be null',
	);
}

Assert::exception(
	fn() => Assert::null(true, 'Custom description'),
	Tester\AssertException::class,
	'Custom description: %a% should be null',
);

Assert::exception(
	fn() => Assert::notNull(null, 'Custom description'),
	Tester\AssertException::class,
	'Custom description: Value should not be null',
);
