<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::null(NULL);

$notNull = array(FALSE, 0, ''. 'NULL');

foreach ($notNull as $value) {
	Assert::exception(function () use ($value) {
		Assert::null($value);
	}, 'Tester\AssertException', '%a% should be NULL');
}

Assert::exception(function () {
	Assert::null(true, 'Custom description');
}, 'Tester\AssertException', 'Custom description: %a% should be NULL');
