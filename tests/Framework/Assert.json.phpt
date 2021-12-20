<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$validJSON = '{"foo": "bar"}';
$notJSON = [0, 1, true, false];
$notValidJSON = ['"foo": "bar"'];

foreach ($notJSON as $value) {
	Assert::exception(function () use ($value) {
		Assert::json($value);
	}, Tester\AssertException::class, '%a% should be string');
}

foreach ($notValidJSON as $value) {
	Assert::exception(function () use ($value) {
		Assert::json($value);
	}, Tester\AssertException::class, '%a% should be valid JSON');
}

Assert::json($validJSON);
