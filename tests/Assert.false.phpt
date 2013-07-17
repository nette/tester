<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::false(FALSE);

$notFalse = array(
	array(TRUE, 'TRUE should be FALSE'),
	array(0, '0 should be FALSE'),
	array(1, '1 should be FALSE'),
	array(NULL, 'NULL should be FALSE'),
	array('FALSE', "'FALSE' should be FALSE"),
);

foreach ($notFalse as $case) {
	list($actual, $message) = $case;
	Assert::exception(function() use ($actual) {
		Assert::false($actual);
	}, 'Tester\AssertException', $message);
}
