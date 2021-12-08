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

// test not countable values
Assert::exception(function () {
	Assert::count(1, null);
}, Tester\AssertException::class, 'NULL should be array or countable object');

Assert::exception(function () {
	Assert::count(1, 1);
}, Tester\AssertException::class, '1 should be array or countable object');

Assert::exception(function () {
	Assert::count(1, 'lorem ipsum');
}, Tester\AssertException::class, '\'lorem ipsum\' should be array or countable object');

Assert::exception(function () {
	Assert::count(1, new Exception('lorem ipsum'));
}, Tester\AssertException::class, 'Exception Exception: lorem ipsum should be array or countable object');

Assert::exception(function () {
	Assert::count(1, [1, 2, 3], 'Custom description');
}, Tester\AssertException::class, 'Custom description: Count 3 should be 1');
