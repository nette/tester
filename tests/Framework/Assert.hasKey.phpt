<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$array = [
	1 => 1,
	'one' => 'one',
];

$string = 'Lorem ipsum';

Assert::hasKey(1, $array);
Assert::hasKey('1', $array);
Assert::hasKey('one', $array);

Assert::hasNotKey(2, $array);
Assert::hasNotKey('two', $array);

Assert::exception(
	fn() => Assert::hasKey(2, $array),
	Tester\AssertException::class,
	'%a% should contain key %a%',
);

Assert::exception(
	fn() => Assert::hasKey('two', $array),
	Tester\AssertException::class,
	'%a% should contain key %a%',
);


Assert::exception(
	fn() => Assert::hasNotKey(1, $array),
	Tester\AssertException::class,
	'%a% should not contain key %a%',
);

Assert::exception(
	fn() => Assert::hasNotKey('one', $array),
	Tester\AssertException::class,
	'%a% should not contain key %a%',
);


Assert::exception(
	fn() => Assert::hasKey('two', $array, 'Custom description'),
	Tester\AssertException::class,
	"Custom description: [1 => 1, 'one' => 'one'] should contain key 'two'",
);

Assert::exception(
	fn() => Assert::hasNotKey('one', $array, 'Custom description'),
	Tester\AssertException::class,
	"Custom description: [1 => 1, 'one' => 'one'] should not contain key 'one'",
);
