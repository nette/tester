<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$contains = [
	['1', '1'],
	['1', 'a1'],
	['1', ['1']],
	['', '1'],
];

$notContains = [
	['2', 'a1'],
	['1', [true]],
];

foreach ($contains as $case) {
	list($expected, $value) = $case;

	Assert::contains($expected, $value);

	Assert::exception(function () use ($expected, $value) {
		Assert::notContains($expected, $value);
	}, 'Tester\AssertException', '%a% should not contain %a%');
}

foreach ($notContains as $case) {
	list($expected, $value) = $case;

	Assert::notContains($case[0], $case[1]);

	Assert::exception(function () use ($expected, $value) {
		Assert::contains($expected, $value);
	}, 'Tester\AssertException', '%a% should contain %a%');
}


Assert::exception(function () {
	Assert::contains(1, 1);
}, 'Tester\AssertException', '1 should be string or array');

Assert::exception(function () {
	Assert::notContains(1, 1);
}, 'Tester\AssertException', '1 should be string or array');

Assert::exception(function () {
	Assert::notContains('', '1');
}, 'Tester\AssertException', "'1' should not contain ''");

Assert::exception(function () {
	Assert::contains('a', '1', 'Custom description');
}, 'Tester\AssertException', "Custom description: '1' should contain 'a'");

Assert::exception(function () {
	Assert::notContains('1', '1', 'Custom description');
}, 'Tester\AssertException', "Custom description: '1' should not contain '1'");
