<?php

use Tester\Assert;
use Tester\DataProvider;

require __DIR__ . '/../bootstrap.php';


test(function () {
	$expect = array(
		'foo' => array(),
		'bar' => array(),
	);

	Assert::same($expect, DataProvider::load('fixtures/dataprovider.ini'));
	Assert::same($expect, DataProvider::load('fixtures/dataprovider.php'));
});


test(function () {
	$expect = array(
		'bar 1.2.3' => array('a' => '1'),
		'bar' => array('b' => '2'),
	);

	Assert::same($expect, DataProvider::load('fixtures/dataprovider.query.ini', ' = bar'));
	Assert::same($expect, DataProvider::load('fixtures/dataprovider.query.php', ' = bar'));
});
