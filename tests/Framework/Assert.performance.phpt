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
	Assert::performance(5, function () {
		password_hash('password', PASSWORD_DEFAULT);
	});
}, Tester\AssertException::class, 'The function can never be evaluated.');
