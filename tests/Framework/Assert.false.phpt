<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::false(false);

$notFalse = [true, 0, 1, null, 'false'];

foreach ($notFalse as $value) {
	Assert::exception(function () use ($value) {
		Assert::false($value);
	}, Tester\AssertException::class, '%a% should be false');
}

Assert::exception(function () {
	Assert::false(true, 'Custom description');
}, Tester\AssertException::class, 'Custom description: %a% should be false');
