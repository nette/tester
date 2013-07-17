<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$equals = array(
	array(1, 1, '1 should not be equal to 1'),
	array('1', '1', "'1' should not be equal to '1'"),
	array(array('1'), array('1'), 'array(1) should not be equal to array(1)'),
	array(array('a' => TRUE, 'b' => FALSE), array('b' => FALSE, 'a' => TRUE), 'array(2) should not be equal to array(2)'),
	array(new stdClass, new stdClass, 'stdClass(0) should not be equal to stdClass(0)'),
	array(array(new stdClass), array(new stdClass), 'array(1) should not be equal to array(1)'),
	array(1/3, 1 - 2/3, '0.33%d% should not be equal to 0.33%d%'),
);

$notEquals = array(
	array(1, 1.0, '1.0 should be equal to 1'),
);

foreach ($equals as $case) {
	list($expected, $actual, $message) = $case;

	Assert::equal($expected, $actual);

	Assert::exception(function() use ($expected, $actual) {
		Assert::notEqual($expected, $actual);
	}, 'Tester\AssertException', $message);
}

foreach ($notEquals as $case) {
	list($expected, $actual, $message) = $case;
	Assert::notEqual($case[0], $case[1]);

	Assert::exception(function() use ($expected, $actual) {
		Assert::equal($expected, $actual);
	}, 'Tester\AssertException', $message);
}

Assert::exception(function(){
	$rec = array();
	$rec[] = & $rec;
	Assert::equal($rec, $rec);
}, 'Exception', 'Nesting level too deep or recursive dependency.');
