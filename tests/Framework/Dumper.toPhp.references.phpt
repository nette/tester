<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Dumper;

require __DIR__ . '/../bootstrap.php';


test('referenced float in array is not modified', function () {
	$x = 1.5;
	$arr = [&$x];
	Assert::same('[1.5]', Dumper::toPhp($arr));
	Assert::same(1.5, $x);
});


test('referenced float in object property is not modified', function () {
	$x = 2.0;
	$obj = new stdClass;
	$obj->x = &$x;
	Assert::match("(object) /* #%h% */ [\n\t'x' => 2.0,\n]", Dumper::toPhp($obj));
	Assert::same(2.0, $x);
});


test('recursive array is left intact', function () {
	$arr = [1.5];
	$arr[] = &$arr;
	Dumper::toPhp($arr);
	Assert::same(1.5, $arr[0]);
	Assert::same([0, 1], array_keys($arr));
});


test('non-finite floats', function () {
	Assert::same('INF', Dumper::toPhp(INF));
	Assert::same('-INF', Dumper::toPhp(-INF));
	Assert::same('NAN', Dumper::toPhp(NAN));
});
