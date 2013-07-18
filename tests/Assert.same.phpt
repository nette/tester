<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$same = array(
	array(1, 1, '1 should not be 1'),
	array('1', '1', "'1' should not be '1'"),
	array(array('1'), array('1'), "array('1') should not be array('1')"),
	array($obj = new stdClass, $obj, 'stdClass(#%a%) should not be stdClass(#%a%)'),
);

$notSame = array(
	array(1, 1.0, '1.0 should be 1'),
	array(array('a' => TRUE, 'b' => FALSE), array('b' => FALSE, 'a' => TRUE), "array('b' => FALSE, 'a' => TRUE) should be array('a' => TRUE, 'b' => FALSE)"),
	array(new stdClass, new stdClass, 'stdClass(#%a%) should be stdClass(#%a%)'),
	array(array(new stdClass), array(new stdClass), 'array(stdClass(#%a%)) should be array(stdClass(#%a%))'),
);

foreach ($same as $case) {
	list($expected, $actual, $message) = $case;

	Assert::same($expected, $actual);

	Assert::exception(function() use ($expected, $actual) {
		Assert::notSame($expected, $actual);
	}, 'Tester\AssertException', $message);
}

foreach ($notSame as $case) {
	list($expected, $actual, $message) = $case;
	Assert::notSame($case[0], $case[1]);

	Assert::exception(function() use ($expected, $actual) {
		Assert::same($expected, $actual);
	}, 'Tester\AssertException', $message);
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
}, 'Tester\AssertException', 'array() should be array(array(...))');
