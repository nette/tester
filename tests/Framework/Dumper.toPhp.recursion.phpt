<?php

use Tester\Assert;
use Tester\Dumper;

require __DIR__ . '/../bootstrap.php';


$arr = array(1, 2, 3);
$arr[] = & $arr;
Assert::match('array(
	1,
	2,
	3,
	array(1, 2, 3, /* Nesting level too deep or recursive dependency */),
)', Dumper::toPhp($arr));


$obj = (object) array('x' => 1, 'y' => 2);
$obj->z = & $obj;
Assert::match("(object) /* #%a% */ array(
	'x' => 1,
	'y' => 2,
	'z' => /* stdClass dumped on line 1 */,
)", Dumper::toPhp($obj));


$var = array(
	$arr,
	$empty = new stdClass,
	$obj,
	$empty,
	$obj,
);
Assert::match("array(
	array(
		1,
		2,
		3,
		array(1, 2, 3, /* Nesting level too deep or recursive dependency */),
	),
	(object) /* #%a% */ array(),
	(object) /* #%a% */ array(
		'x' => 1,
		'y' => 2,
		'z' => /* stdClass dumped on line 9 */,
	),
	(object) /* #%a% */ array(),
	/* stdClass dumped on line 9 */,
)", Dumper::toPhp($var));
