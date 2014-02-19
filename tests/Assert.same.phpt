<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$same = array(
	array(1, 1),
	array('1', '1'),
	array(array('1'), array('1')),
	array($obj = new stdClass, $obj),
);

$notSame = array(
	array(1, 1.0),
	array(array('a' => TRUE, 'b' => FALSE), array('b' => FALSE, 'a' => TRUE)),
	array(array('a', 'b'), array('b', 'a')),
	array(array('a', 'b'), array(1 => 'b', 0 => 'a')),
	array(new stdClass, new stdClass),
	array(array(new stdClass), array(new stdClass)),
);

foreach ($same as $case) {
	list($expected, $value) = $case;

	Assert::same($expected, $value);

	Assert::exception(function() use ($expected, $value) {
		Assert::notSame($expected, $value);
	}, 'Tester\AssertException', '%a% should not be %a%');
}

foreach ($notSame as $case) {
	list($expected, $value) = $case;

	Assert::notSame($case[0], $case[1]);

	Assert::exception(function() use ($expected, $value) {
		Assert::same($expected, $value);
	}, 'Tester\AssertException', '%a% should be %a%');
}


if ((PHP_VERSION_ID >= 50315 && PHP_VERSION_ID < 50400) || PHP_VERSION_ID >= 50405) {
	$rec = array();
	$rec[] = & $rec;
	Assert::same($rec, $rec);
}

Assert::exception(function() {
	$rec = array();
	$rec[] = & $rec;
	Assert::same($rec, array());
}, 'Tester\AssertException');
