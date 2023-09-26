<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$truthy = [1, '1', [1], true, new stdClass];
$notTruthy = [0, '', '0', [], null, new SimpleXMLElement('<xml></xml>')];

foreach ($truthy as $value) {
	Assert::truthy($value);

	Assert::exception(
		fn() => Assert::falsey($value),
		Tester\AssertException::class,
		'%a% should be falsey',
	);
}

foreach ($notTruthy as $value) {
	Assert::falsey($value);

	Assert::exception(
		fn() => Assert::truthy($value),
		Tester\AssertException::class,
		'%a% should be truthy',
	);
}

Assert::exception(
	fn() => Assert::truthy(false, 'Custom description'),
	Tester\AssertException::class,
	'Custom description: %a% should be truthy',
);

Assert::exception(
	fn() => Assert::falsey(true, 'Custom description'),
	Tester\AssertException::class,
	'Custom description: %a% should be falsey',
);
