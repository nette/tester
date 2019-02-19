<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$obj1 = new stdClass;
$obj1->{'0'} = 'a';
$obj1->{'1'} = 'b';

$obj2 = new stdClass;
$obj2->{'1'} = 'b';
$obj2->{'0'} = 'a';

$obj3 = new stdClass;
$obj3->x = $obj3->y = new stdClass;

$obj4 = new stdClass;
$obj4->x = new stdClass;
$obj4->y = new stdClass;

$deep1 = $deep2 = new stdClass;
$deep1->x = $deep2->x = $deep1;

$float1 = 1 / 3;
$float2 = 1 - 2 / 3;

$equals = [
	[1, 1],
	['1', '1'],
	[['1'], ['1']],
	[['a', 'b'], [1 => 'b', 0 => 'a']],
	[['a' => true, 'b' => false], ['b' => false, 'a' => true]],
	[$float1, $float2],
	[$float1 * 1e9, $float2 * 1e9],
	[$float1 - $float2, 0.0],
	[$float1 - $float2, $float2 - $float1],
	[0.0, 0.0],
	[INF, INF],
	[[0 => 'a', 'str' => 'b'], ['str' => 'b', 0 => 'a']],
	[$deep1, $deep2],
	[Tester\Expect::type('int'), 1],
];

$notEquals = [
	[1, 1.0],
	[new stdClass, new stdClass],
	[[new stdClass], [new stdClass]],
	[$obj3, $obj4],
	[INF, -INF],
	[['a', 'b'], ['b', 'a']],
	[NAN, NAN],
	[Tester\Expect::type('int'), '1', 'string should be int'],
];



foreach ($equals as [$expected, $value]) {
	Assert::equal($expected, $value, matchIdentity: true);
}

foreach ($notEquals as [$expected, $value]) {
	Assert::exception(function () use ($expected, $value) {
		Assert::equal($expected, $value, matchIdentity: true);
	}, Tester\AssertException::class, '%a% should be %a%');
}

Assert::exception(function () {
	$rec = [];
	$rec[] = &$rec;
	Assert::equal($rec, $rec, matchIdentity: true);
}, Exception::class, 'Nesting level too deep or recursive dependency.');

Assert::exception(function () {
	Assert::equal(true, false, 'Custom description', matchIdentity: true);
}, Tester\AssertException::class, 'Custom description: %a% should be %a%');
