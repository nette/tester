<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$truthy = [1, '1', [1], true, new stdClass];
$notTruthy = [0, '', '0', [], null, new SimpleXMLElement('<xml></xml>')];

foreach ($truthy as $value) {
	Assert::truthy($value);

	Assert::exception(function () use ($value) {
		Assert::falsey($value);
	}, 'Tester\AssertException', '%a% should be falsey');
}

foreach ($notTruthy as $value) {
	Assert::falsey($value);

	Assert::exception(function () use ($value) {
		Assert::truthy($value);
	}, 'Tester\AssertException', '%a% should be truthy');
}

Assert::exception(function () {
	Assert::truthy(false, 'Custom description');
}, 'Tester\AssertException', 'Custom description: %a% should be truthy');

Assert::exception(function () {
	Assert::falsey(true, 'Custom description');
}, 'Tester\AssertException', 'Custom description: %a% should be falsey');
