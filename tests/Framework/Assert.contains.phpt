<?php

declare(strict_types=1);

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

foreach ($contains as [$expected, $value]) {
	Assert::contains($expected, $value);

	Assert::exception(function () use ($expected, $value) {
		Assert::notContains($expected, $value);
	}, Tester\AssertException::class, '%a% should not contain %a%');
}

foreach ($notContains as [$expected, $value]) {
	Assert::notContains($expected, $value);

	Assert::exception(function () use ($expected, $value) {
		Assert::contains($expected, $value);
	}, Tester\AssertException::class, '%a% should contain %a%');
}


Assert::exception(function () {
	Assert::contains(1, 1);
}, Tester\AssertException::class, '1 should be string or array');

Assert::exception(function () {
	Assert::contains(1, '1');
}, Tester\AssertException::class, 'Needle 1 should be string');

Assert::exception(function () {
	Assert::notContains(1, 1);
}, Tester\AssertException::class, '1 should be string or array');

Assert::exception(function () {
	Assert::notContains(1, '1');
}, Tester\AssertException::class, 'Needle 1 should be string');

Assert::exception(function () {
	Assert::notContains('', '1');
}, Tester\AssertException::class, "'1' should not contain ''");

Assert::exception(function () {
	Assert::contains('a', '1', 'Custom description');
}, Tester\AssertException::class, "Custom description: '1' should contain 'a'");

Assert::exception(function () {
	Assert::notContains('1', '1', 'Custom description');
}, Tester\AssertException::class, "Custom description: '1' should not contain '1'");
