<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Environment;

require __DIR__ . '/../bootstrap.php';

$key = count($_SERVER['argv']);
$file = realpath(__DIR__ . '/fixtures/dataprovider.ini');


test('', function () use ($key, $file) {
	$_SERVER['argv'][$key] = "--dataprovider=0|$file";
	Assert::same(['dataset-0'], Environment::loadData());

	$_SERVER['argv'][$key] = "--dataprovider=1|$file";
	Assert::same(['dataset-1'], Environment::loadData());

	$_SERVER['argv'][$key] = "--dataprovider=foo|$file";
	Assert::same(['dataset-foo'], Environment::loadData());

	$_SERVER['argv'][$key] = "--dataprovider=bar 1|$file";
	Assert::same(['dataset-bar-1'], Environment::loadData());

	$_SERVER['argv'][$key] = "--dataprovider=bar 2|$file";
	Assert::same(['dataset-bar-2'], Environment::loadData());
});


test('', function () use ($key, $file) {
	$_SERVER['argv'][$key] = "--dataprovider=bar|$file";

	Assert::exception(
		fn() => Environment::loadData(),
		Exception::class,
		"Missing dataset 'bar' from data provider '%a%'.",
	);
});


test('', function () use ($key, $file) {
	unset($_SERVER['argv'][$key]);

	Assert::exception(
		fn() => Environment::loadData(),
		Exception::class,
		'Missing annotation @dataProvider.',
	);
});
