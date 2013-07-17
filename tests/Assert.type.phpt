<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$cases = array(
	array('\stdClass', new stdClass),
	array('x', new stdClass, 'stdClass(0) should be instance of x'),
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
