<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::same(1, 1);
Assert::same('1', '1');
Assert::same(array('1'), array('1'));
Assert::same($obj = new stdClass, $obj);

$rec = array();
$rec[] = & $rec;
// Assert::same($rec, $rec); // works since PHP 5.3.15 and 5.4.5

Assert::exception(function(){
	Assert::same(1, 1.0);
}, 'Tester\AssertException', '1.0 should be 1');

Assert::exception(function(){
	Assert::same(array('a' => true, 'b' => false), array('b' => false, 'a' => true));
}, 'Tester\AssertException', 'array(2) should be array(2)');

Assert::exception(function(){
	Assert::same(new stdClass, new stdClass);
}, 'Tester\AssertException', 'stdClass(0) should be stdClass(0)');

Assert::exception(function(){
	$rec = array();
	$rec[] = & $rec;
	Assert::same($rec, array());
}, 'Tester\AssertException', 'array(0) should be array(1)');

Assert::notSame(1, 1.0);

Assert::exception(function(){
	Assert::notSame(1, 1);
}, 'Tester\AssertException', '1 should not be 1');
