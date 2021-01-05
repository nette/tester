<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\DataProvider;

require __DIR__ . '/../bootstrap.php';


test(function () {
	$expect = [
		1 => [],
		'foo' => [],
		'bar' => [],
	];

	Assert::same($expect, DataProvider::load('fixtures/dataprovider.ini'));
	Assert::same($expect, DataProvider::load('fixtures/dataprovider.php'));
});


test(function () {
	$expect = [
		'bar 1.2.3' => ['a' => '1'],
		'bar' => ['b' => '2'],
	];

	Assert::same($expect, DataProvider::load('fixtures/dataprovider.query.ini', ' = bar'));
	Assert::same($expect, DataProvider::load('fixtures/dataprovider.query.php', ' = bar'));
});


test(function () {
	Assert::same([], DataProvider::load('fixtures/dataprovider.query.ini', 'non-existent'));
	Assert::same([], DataProvider::load('fixtures/dataprovider.query.php', 'non-existent'));
});
