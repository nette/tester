<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$contains = array(
	array('1', '1'),
	array('1', 'a1'),
	array('1', array('1')),
);

$notContains = array(
	array('2', 'a1'),
	array('1', array(TRUE)),
);

foreach ($contains as $case) {
	list($expected, $value) = $case;

	Assert::contains($expected, $value);

	Assert::exception(function() use ($expected, $value) {
		Assert::notContains($expected, $value);
	}, 'Tester\AssertException', "%1 should not contain %2");
}

foreach ($notContains as $case) {
	list($expected, $value) = $case;

	Assert::notContains($case[0], $case[1]);

	Assert::exception(function() use ($expected, $value) {
		Assert::contains($expected, $value);
	}, 'Tester\AssertException', "%1 should contain %2");
}


Assert::exception(function() {
	Assert::contains(1, 1);
}, 'Tester\AssertException', '%1 should be string or array');

Assert::exception(function() {
	Assert::notContains(1, 1);
}, 'Tester\AssertException', '%1 should be string or array');
