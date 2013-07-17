<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::true(TRUE);

$notTrue = array(
	array(FALSE, 'FALSE should be TRUE'),
	array(0, '0 should be TRUE'),
	array(1, '1 should be TRUE'),
	array(NULL, 'NULL should be TRUE'),
	array('TRUE', "'TRUE' should be TRUE"),
);

foreach ($notTrue as $case) {
	list($actual, $message) = $case;
	Assert::exception(function() use ($actual) {
		Assert::true($actual);
	}, 'Tester\AssertException', $message);
}
