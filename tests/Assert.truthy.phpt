<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$truthy = array(1, '1', array(1), TRUE, new stdClass);
$notTruthy = array(0, '', '0', array(), NULL, new SimpleXMLElement('<xml></xml>'));

foreach ($truthy as $value) {
	Assert::truthy($value);

	Assert::exception(function() use ($value) {
		Assert::falsey($value);
	}, 'Tester\AssertException', '%a% should be falsey');
}

foreach ($notTruthy as $value) {
	Assert::falsey($value);

	Assert::exception(function() use ($value) {
		Assert::truthy($value);
	}, 'Tester\AssertException', '%a% should be truthy');
}
