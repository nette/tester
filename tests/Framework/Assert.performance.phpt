<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// Positive
Assert::performance(1000, function () {
	$sum = 5 + 3;
});

// Negative
Assert::exception(function () {
	Assert::performance(500, function () {
		sleep(1);
	});
}, Tester\AssertException::class, 'Function is too slow. Limit \'%f% ms\' expected, but real time is \'%f% ms\'.');
