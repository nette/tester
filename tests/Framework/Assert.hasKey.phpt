<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$array = [
	1 => 1,
	'one' => 'one',
];

$string= 'Lorem ipsum';

Assert::hasKey(1, $array);
Assert::hasKey('1', $array);
Assert::hasKey('one', $array);

Assert::hasNotKey(2, $array);
Assert::hasNotKey('two', $array);

foreach ([[], true, false, null, new stdClass, 1.0] as $key) {
	Assert::exception(function () use ($key, $array) {
		Assert::hasKey($key, $array);
	}, Tester\AssertException::class, 'Key %a% should be string or integer');

	Assert::exception(function () use ($key, $array) {
		Assert::hasNotKey($key, $array);
	}, Tester\AssertException::class, 'Key %a% should be string or integer');
}

Assert::exception(function () use ($array) {
	Assert::hasKey(2, $array);
}, Tester\AssertException::class, '%a% should contain key %a%');

Assert::exception(function () use ($array) {
	Assert::hasKey('two', $array);
}, Tester\AssertException::class, '%a% should contain key %a%');


Assert::exception(function () use ($array) {
	Assert::hasNotKey(1, $array);
}, Tester\AssertException::class, '%a% should not contain key %a%');

Assert::exception(function () use ($array) {
	Assert::hasNotKey('one', $array);
}, Tester\AssertException::class, '%a% should not contain key %a%');


Assert::exception(function () use ($array) {
	Assert::hasKey('two', $array, 'Custom description');
}, Tester\AssertException::class, "Custom description: [1 => 1, 'one' => 'one'] should contain key 'two'");

Assert::exception(function () use ($array) {
	Assert::hasNotKey('one', $array, 'Custom description');
}, Tester\AssertException::class, "Custom description: [1 => 1, 'one' => 'one'] should not contain key 'one'");
