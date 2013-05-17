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
}, 'Tester\AssertException', 'Failed asserting that 1.0 is identical to expected 1');

Assert::exception(function(){
	Assert::same(array('a' => true, 'b' => false), array('b' => false, 'a' => true));
}, 'Tester\AssertException', 'Failed asserting that array(2) is identical to expected array(2)');

Assert::exception(function(){
	Assert::same(new stdClass, new stdClass);
}, 'Tester\AssertException', 'Failed asserting that stdClass(0) is identical to expected stdClass(0)');

Assert::exception(function(){
	$rec = array();
	$rec[] = & $rec;
	Assert::same($rec, array());
}, 'Tester\AssertException', 'Failed asserting that array(0) is identical to expected array(1)');

Assert::notSame(1, 1.0);

Assert::exception(function(){
	Assert::notSame(1, 1);
}, 'Tester\AssertException', 'Failed asserting that 1 is not identical to expected 1');
