<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::equal(1, 1);
Assert::equal('1', '1');
Assert::equal(array('1'), array('1'));
Assert::equal(array('a' => true, 'b' => false), array('b' => false, 'a' => true));
Assert::equal(new stdClass, new stdClass);
Assert::equal(array(new stdClass), array(new stdClass));

Assert::exception(function(){
	Assert::equal(1, 1.0);
}, 'Tester\AssertException', '1.0 should be equal to 1');

Assert::exception(function(){
	$rec = array();
	$rec[] = & $rec;
	Assert::equal($rec, $rec);
}, 'Exception', 'Nesting level too deep or recursive dependency.');

Assert::notEqual(1, 1.0);

Assert::exception(function(){
	Assert::notEqual(1, 1);
}, 'Tester\AssertException', '1 should not be equal to 1');
