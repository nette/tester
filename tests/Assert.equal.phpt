<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$obj1 = new stdClass;
$obj1->{'0'} = 'a';
$obj1->{'1'} = 'b';

$obj2 = new stdClass;
$obj2->{'1'} = 'b';
$obj2->{'0'} = 'a';

$equals = array(
	array(1, 1),
	array('1', '1'),
	array(array('1'), array('1')),
	array(array('a', 'b'), array(1 => 'b', 0 => 'a')),
	array(array('a' => TRUE, 'b' => FALSE), array('b' => FALSE, 'a' => TRUE)),
	array(new stdClass, new stdClass),
	array(array(new stdClass), array(new stdClass)),
	array(1/3, 1 - 2/3),
	array($obj1, $obj2),
);

$notEquals = array(
	array(1, 1.0),
	array(array('a', 'b'), array('b', 'a')),
);

foreach ($equals as $case) {
	list($expected, $value) = $case;

	Assert::equal($expected, $value);

	Assert::exception(function() use ($expected, $value) {
		Assert::notEqual($expected, $value);
	}, 'Tester\AssertException', '%1 should not be equal to %2');
}

foreach ($notEquals as $case) {
	list($expected, $value) = $case;

	Assert::notEqual($case[0], $case[1]);

	Assert::exception(function() use ($expected, $value) {
		Assert::equal($expected, $value);
	}, 'Tester\AssertException', '%1 should be equal to %2');
}

Assert::exception(function() {
	$rec = array();
	$rec[] = & $rec;
	Assert::equal($rec, $rec);
}, 'Exception', 'Nesting level too deep or recursive dependency.');
