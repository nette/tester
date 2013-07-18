<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$contains = array(
	array('1', '1', "'1' should not contain '1'"),
	array('1', 'a1', "'a1' should not contain '1'"),
	array('1', array('1'), "array(1) should not contain '1'"),
);

$notContains = array(
	array('2', 'a1', "'a1' should contain '2'"),
	array('1', array(TRUE), "array(1) should contain '1'"),
);

foreach ($contains as $case) {
	list($expected, $actual, $message) = $case;

	Assert::contains($expected, $actual);

	Assert::exception(function() use ($expected, $actual) {
		Assert::notContains($expected, $actual);
	}, 'Tester\AssertException', $message);
}

foreach ($notContains as $case) {
	list($expected, $actual, $message) = $case;
	Assert::notContains($case[0], $case[1]);

	Assert::exception(function() use ($expected, $actual) {
		Assert::contains($expected, $actual);
	}, 'Tester\AssertException', $message);
}


Assert::exception(function() {
	Assert::contains(1, 1);
}, 'Tester\AssertException', '1 should be string or array');

Assert::exception(function() {
	Assert::notContains(1, 1);
}, 'Tester\AssertException', '1 should be string or array');
