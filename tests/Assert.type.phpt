<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$cases = array(
	array('\stdClass', new stdClass),
	array('STDCLASS', new stdClass),
	array('x', new stdClass, 'stdClass(0) should be instance of x'),
	array('Int', new stdClass, 'stdClass(0) should be instance of Int'),
	array('int', new stdClass, 'stdClass(0) should be int'),
	array('array', array()),
	array('bool', TRUE),
	array('callable', function(){}),
	array('float', 0.0),
	array('int', 0),
	array('integer', 0),
	array('null', NULL),
	array('object', new stdClass),
	array('resource', fopen(__FILE__, 'r')),
	array('scalar', 'x'),
	array('string', 'x'),
);

foreach ($cases as $case) {
	@list($type, $actual, $message) = $case;
	if ($message) {
		Assert::exception(function() use ($type, $actual) {
			Assert::type($type, $actual);
		}, 'Tester\AssertException', $message);
	} else {
		Assert::type($type, $actual);
	}
}
