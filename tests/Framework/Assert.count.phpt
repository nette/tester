<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

// test correct count
Assert::count(0, []);
Assert::count(2, [1, 1]);
Assert::count(4, ['1', '1', 1, 2]);
Assert::count(2, SplFixedArray::fromArray([1, 2]));

// test if asserts are counted
Assert::equal(4, Assert::$counter);

// test wrong count
Assert::exception(function () {
	Assert::count(1, [1, 2, 3]);
}, Tester\AssertException::class, 'Count 3 should be 1');

// test custom description
Assert::exception(function () {
	Assert::count(1, [1, 2, 3], 'Custom description');
}, Tester\AssertException::class, 'Custom description: Count 3 should be 1');
