<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$cases = [
	['\stdClass', new stdClass],
	['STDCLASS', new stdClass],
	['x', new stdClass, 'stdClass should be instance of x'],
	['Int', new stdClass, 'stdClass should be instance of Int'],
	['int', new stdClass, 'object should be int'],
	['array', []],
	['bool', TRUE],
	['callable', function () {}],
	['float', 0.0],
	['int', 0],
	['integer', 0],
	['null', NULL],
	['object', new stdClass],
	['resource', fopen(__FILE__, 'r')],
	['scalar', 'x'],
	['string', 'x'],
	['list', NULL, 'NULL should be list'],
	['list', []],
	['list', [1]],
	['list', [4 => 1], 'array(4 => 1) should be list'],
];

foreach ($cases as $case) {
	@list($type, $value, $message) = $case;
	if ($message) {
		Assert::exception(function () use ($type, $value) {
			Assert::type($type, $value);
		}, 'Tester\AssertException', $message);
	} else {
		Assert::type($type, $value);
	}
}


$arr = [];
$arr[] = & $arr;
Assert::type('list', $arr);

Assert::exception(function () {
	Assert::type('int', 'string', 'Custom description');
}, 'Tester\AssertException', 'Custom description: string should be int');
