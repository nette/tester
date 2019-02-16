<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Expect;

require __DIR__ . '/../bootstrap.php';


Assert::equal(
	['a' => Expect::true(), 'b' => Expect::same(10.0)],
	['a' => true, 'b' => 10.0]
);


Assert::exception(function () {
	Assert::equal(
		['a' => Expect::true(), 'b' => Expect::same(10.0)],
		['a' => true, 'b' => 10]
	);
}, Tester\AssertException::class, '10 should be 10.0');


Assert::equal(
	[
		'a' => Expect::same(['k1' => 'v1', 'k2' => 'v2']),
		'b' => true,
	],
	[
		'b' => true,
		'a' => ['k1' => 'v1', 'k2' => 'v2'],
	]
);


Assert::exception(function () {
	Assert::equal(
		[
			'a' => Expect::same(['k1' => 'v1', 'k2' => 'v2']),
			'b' => true,
		],
		[
			'b' => true,
			'a' => ['k2' => 'v2', 'k1' => 'v1'],
		]
	);
}, Tester\AssertException::class, "['k2' => 'v2', 'k1' => 'v1'] should be ['k1' => 'v1', 'k2' => 'v2']");
