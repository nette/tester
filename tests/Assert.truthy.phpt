<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$truthy = array(1, '1', array(1), TRUE, new stdClass);
$notTruthy = array(0, '', '0', array(), NULL, new SimpleXMLElement('<xml></xml>'));

foreach ($truthy as $actual) {
	Assert::truthy($actual);
	Assert::exception(function() use ($actual) {
		Assert::falsey($actual);
	}, 'Tester\AssertException');
}

foreach ($notTruthy as $actual) {
	Assert::falsey($actual);

	Assert::exception(function() use ($actual) {
		Assert::truthy($actual);
	}, 'Tester\AssertException');
}
