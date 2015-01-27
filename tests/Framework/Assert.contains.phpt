<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$contains = array(
	array('1', '1'),
	array('1', 'a1'),
	array('1', array('1')),
	array('', '1'),
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
	}, 'Tester\AssertException', "%a% should not contain %a%");
}

foreach ($notContains as $case) {
	list($expected, $value) = $case;

	Assert::notContains($case[0], $case[1]);

	Assert::exception(function() use ($expected, $value) {
		Assert::contains($expected, $value);
	}, 'Tester\AssertException', "%a% should contain %a%");
}


Assert::exception(function() {
	Assert::contains(1, 1);
}, 'Tester\AssertException', '1 should be string or array');

Assert::exception(function() {
	Assert::notContains(1, 1);
}, 'Tester\AssertException', '1 should be string or array');

Assert::exception(function() {
	Assert::notContains('', '1');
}, 'Tester\AssertException', "'1' should not contain ''");
