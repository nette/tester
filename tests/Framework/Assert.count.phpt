<?php

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
}, 'Tester\AssertException', 'Count 3 should be 1');

// test not countable values
Assert::exception(function () {
	Assert::count(1, null);
}, 'Tester\AssertException', 'NULL should be array or countable object');

Assert::exception(function () {
	Assert::count(1, 1);
}, 'Tester\AssertException', '1 should be array or countable object');

Assert::exception(function () {
	Assert::count(1, 'lorem ipsum');
}, 'Tester\AssertException', '\'lorem ipsum\' should be array or countable object');

Assert::exception(function () {
	Assert::count(1, new \Exception('lorem ipsum'));
}, 'Tester\AssertException', 'Exception Exception: lorem ipsum should be array or countable object');

Assert::exception(function () {
	Assert::count(1, [1, 2, 3], 'Custom description');
}, 'Tester\AssertException', 'Custom description: Count 3 should be 1');
