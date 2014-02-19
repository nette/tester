<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::nan(NAN);

$notNan = array(FALSE, 0, NULL, '', 'NAN');

foreach ($notNan as $value) {
	Assert::exception(function() use ($value) {
		Assert::nan($value);
	}, 'Tester\AssertException', '%a% should be NAN');
}
