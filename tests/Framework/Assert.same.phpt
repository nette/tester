<?php

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
	[['a' => TRUE, 'b' => FALSE], ['b' => FALSE, 'a' => TRUE]],
	[['a', 'b'], ['b', 'a']],
	[['a', 'b'], [1 => 'b', 0 => 'a']],
	[new stdClass, new stdClass],
	[[new stdClass], [new stdClass]],
];

foreach ($same as $case) {
	list($expected, $value) = $case;

	Assert::same($expected, $value);

	Assert::exception(function () use ($expected, $value) {
		Assert::notSame($expected, $value);
	}, 'Tester\AssertException', '%a% should not be %a%');
}

foreach ($notSame as $case) {
	list($expected, $value) = $case;

	Assert::notSame($case[0], $case[1]);

	Assert::exception(function () use ($expected, $value) {
		Assert::same($expected, $value);
	}, 'Tester\AssertException', '%a% should be %a%');
}


if ((PHP_VERSION_ID >= 50315 && PHP_VERSION_ID < 50400) || PHP_VERSION_ID >= 50405) {
	$rec = [];
	$rec[] = & $rec;
	Assert::same($rec, $rec);
}

Assert::exception(function () {
	$rec = [];
	$rec[] = & $rec;
	Assert::same($rec, []);
}, 'Tester\AssertException');

Assert::exception(function () {
	Assert::same(true, false, 'Custom description');
}, 'Tester\AssertException', 'Custom description: %a% should be %a%');

Assert::exception(function () {
	Assert::notSame(true, true, 'Custom description');
}, 'Tester\AssertException', 'Custom description: %a% should not be %a%');
