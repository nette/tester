<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$obj1 = new stdClass;
$obj1->{'0'} = 'a';
$obj1->{'1'} = 'b';

$obj2 = new stdClass;
$obj2->{'1'} = 'b';
$obj2->{'0'} = 'a';

$deep1 = $deep2 = new stdClass;
$deep1->x = $deep2->x = $deep1;

$float1 = 1 / 3;
$float2 = 1 - 2 / 3;

$equals = array(
	array(1, 1),
	array('1', '1'),
	array(array('1'), array('1')),
	array(array('a', 'b'), array(1 => 'b', 0 => 'a')),
	array(array('a' => TRUE, 'b' => FALSE), array('b' => FALSE, 'a' => TRUE)),
	array(new stdClass, new stdClass),
	array(array(new stdClass), array(new stdClass)),
	array($float1, $float2),
	array($float1 * 1e9, $float2 * 1e9),
	array($float1 - $float2, 0.0),
	array($float1 - $float2, $float2 - $float1),
	array(0.0, 0.0),
	array(INF, INF),
	array($obj1, $obj2),
	array(array(0 => 'a', 'str' => 'b'), array('str' => 'b', 0 => 'a')),
	array($deep1, $deep2),
);

$notEquals = array(
	array(1, 1.0),
	array(INF, -INF),
	array(NAN, NAN),
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

$rec1 = new stdClass();
$rec1->foo = 'foo';
$rec1->b = new stdClass();
$rec1->b->bar = 'bar';
$rec1->b->c = $rec1;

$rec2 = new stdClass();
$rec2->foo = 'foo';
$rec2->b = new stdClass();
$rec2->b->bar = 'bar';
$rec2->b->c = $rec2;

Assert::equal($rec1, $rec2);
