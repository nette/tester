<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::true(true);

$notTrue = [false, 0, 1, null, 'true'];

foreach ($notTrue as $value) {
	Assert::exception(function () use ($value) {
		Assert::true($value);
	}, Tester\AssertException::class, '%a% should be true');
}

Assert::exception(function () {
	Assert::true(false, 'Custom description');
}, Tester\AssertException::class, 'Custom description: %a% should be true');
