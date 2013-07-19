<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$cases = array(
	array('\stdClass', new stdClass),
	array('STDCLASS', new stdClass),
	array('x', new stdClass, '%1 should be instance of x'),
	array('Int', new stdClass, '%1 should be instance of Int'),
	array('int', new stdClass, '%1 should be int'),
	array('array', array()),
	array('bool', TRUE),
	array('callable', function() {}),
	array('float', 0.0),
	array('int', 0),
	array('integer', 0),
	array('null', NULL),
	array('object', new stdClass),
	array('resource', fopen(__FILE__, 'r')),
	array('scalar', 'x'),
	array('string', 'x'),
	array('list', NULL, '%1 should be list'),
	array('list', array()),
	array('list', array(1)),
	array('list', array(4 => 1), '%1 should be list'),
);

foreach ($cases as $case) {
	@list($type, $value, $message) = $case;
	if ($message) {
		Assert::exception(function() use ($type, $value) {
			Assert::type($type, $value);
		}, 'Tester\AssertException', $message);
	} else {
		Assert::type($type, $value);
	}
}


$arr = array();
$arr[] = & $arr;
Assert::type('list', $arr);
