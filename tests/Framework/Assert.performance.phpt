<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// Positive
Assert::performance(1000, function () {
	$sum = 5 + 3;
});

// Negative
$e = null;
try {
	Assert::performance(5, function () {
		password_hash('password', PASSWORD_DEFAULT);
	});
} catch (\Tester\AssertException $e) {
}

if ($e === null) {
	Assert::fail('Performance password function must throw exception.');
}
