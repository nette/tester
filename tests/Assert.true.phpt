<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::true(TRUE);

$notTrue = array(FALSE, 0, 1, NULL, 'TRUE');

foreach ($notTrue as $value) {
	Assert::exception(function() use ($value) {
		Assert::true($value);
	}, 'Tester\AssertException', '%a% should be TRUE');
}
