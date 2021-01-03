<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\DataProvider;

require __DIR__ . '/../bootstrap.php';


test(function () {
	$expect = [
		1 => ['integer' => 'abc'],
		2 => ['integer' => 'def'],
		'foo' => [],
		'bar' => [],
	];

	Assert::same($expect, DataProvider::load('fixtures/dataprovider.ini'));
	Assert::same($expect, DataProvider::load('fixtures/dataprovider.php'));

	foreach (array_keys($expect) as $key) {
		Assert::same([$key => $expect[$key]], DataProvider::load('fixtures/dataprovider.ini', (string) $key));
		Assert::same([$key => $expect[$key]], DataProvider::load('fixtures/dataprovider.php', (string) $key));
	}
});


test(function () {
	$expect = [
		'bar 1.2.3' => ['a' => '1'],
		'bar' => ['b' => '2'],
	];

	Assert::same($expect, DataProvider::load('fixtures/dataprovider.query.ini', ' = bar'));
	Assert::same($expect, DataProvider::load('fixtures/dataprovider.query.php', ' = bar'));
});
