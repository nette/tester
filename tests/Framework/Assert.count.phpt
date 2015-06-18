<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

// test correct count
Assert::count(0, array());
Assert::count(2, array(1, 1));
Assert::count(4, array('1', '1', 1, 2));
Assert::count(2, SplFixedArray::fromArray(array(1, 2)));

// test if asserts are counted
Assert::equal(4, Assert::$counter);

// test wrong count
Assert::exception(function () {
	Assert::count(1, array(1, 2, 3));
}, 'Tester\AssertException', 'Count 3 should be 1');

// test not countable values
Assert::exception(function () {
	Assert::count(1, NULL);
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
