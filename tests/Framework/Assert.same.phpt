<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$same = [
	[1, 1],
	['1', '1'],
	[['1'], ['1']],
	[$obj = new stdClass, $obj],
];

$notSame = [
	[1, 1.0],
	[['a' => true, 'b' => false], ['b' => false, 'a' => true]],
	[['a', 'b'], ['b', 'a']],
	[['a', 'b'], [1 => 'b', 0 => 'a']],
	[new stdClass, new stdClass],
	[[new stdClass], [new stdClass]],
	[Tester\Expect::type('int'), 1],
];

foreach ($same as [$expected, $value]) {
	Assert::same($expected, $value);

	Assert::exception(
		fn() => Assert::notSame($expected, $value),
		Tester\AssertException::class,
		'%a% should not be %a%',
	);
}

foreach ($notSame as [$expected, $value]) {
	Assert::notSame($expected, $value);

	Assert::exception(
		fn() => Assert::same($expected, $value),
		Tester\AssertException::class,
		'%a% should be %a%',
	);
}


$rec = [];
$rec[] = &$rec;
Assert::same($rec, $rec);

Assert::exception(
	function () {
		$rec = [];
		$rec[] = &$rec;
		Assert::same($rec, []);
	},
	Tester\AssertException::class,
);

Assert::exception(
	fn() => Assert::same(true, false, 'Custom description'),
	Tester\AssertException::class,
	'Custom description: %a% should be %a%',
);

Assert::exception(
	fn() => Assert::notSame(true, true, 'Custom description'),
	Tester\AssertException::class,
	'Custom description: %a% should not be %a%',
);
