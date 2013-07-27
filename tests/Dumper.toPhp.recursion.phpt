<?php

use Tester\Assert,
	Tester\Dumper;

require __DIR__ . '/bootstrap.php';


$arr = array(1, 2, 3);
$arr[] = & $arr;
Assert::match( 'array(
	1,
	2,
	3,
	array(1, 2, 3, /* Nesting level too deep or recursive dependency */),
)', Dumper::toPhp($arr) );


$arr = (object) array('x' => 1, 'y' => 2);
$arr->z = & $arr;
Assert::match( "(object) array(
	'x' => 1,
	'y' => 2,
	'z' => /* Nesting level too deep or recursive dependency */,
)", Dumper::toPhp($arr) );
