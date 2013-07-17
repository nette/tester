<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::null(NULL);

$notNull = array(
	array(FALSE, 'FALSE should be NULL'),
	array(0, '0 should be NULL'),
	array('', "'' should be NULL"),
	array('NULL', "'NULL' should be NULL"),
);

foreach ($notNull as $case) {
	list($actual, $message) = $case;
	Assert::exception(function() use ($actual) {
		Assert::null($actual);
	}, 'Tester\AssertException', $message);
}
