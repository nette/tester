<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Dumper;

require __DIR__ . '/../bootstrap.php';


$arr = [1, 2, 3];
$arr[] = &$arr;
Assert::match('[
	1,
	2,
	3,
	[1, 2, 3, /* Nesting level too deep or recursive dependency */],
]', Dumper::toPhp($arr));


$obj = (object) ['x' => 1, 'y' => 2];
$obj->z = &$obj;
Assert::match("(object) /* #%a% */ [
	'x' => 1,
	'y' => 2,
	'z' => /* stdClass dumped on line 1 */,
]", Dumper::toPhp($obj));


$var = [
	$arr,
	$empty = new stdClass,
	$obj,
	$empty,
	$obj,
];
Assert::match("[
	[
		1,
		2,
		3,
		[1, 2, 3, /* Nesting level too deep or recursive dependency */],
	],
	(object) /* #%a% */ [],
	(object) /* #%a% */ [
		'x' => 1,
		'y' => 2,
		'z' => /* stdClass dumped on line 9 */,
	],
	(object) /* #%a% */ [],
	/* stdClass dumped on line 9 */,
]", Dumper::toPhp($var));
