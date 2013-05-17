<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::contains('1', '1');
Assert::contains('1', 'a1');
Assert::contains('1', array('1'));

Assert::exception(function(){
	Assert::contains(1, 1);
}, 'Tester\AssertException', 'Failed asserting that 1 is string or array');

Assert::exception(function(){
	Assert::contains('2', 'a1');
}, 'Tester\AssertException', 'Failed asserting that "a1" contains "2"');

Assert::exception(function(){
	Assert::contains('1', array(TRUE));
}, 'Tester\AssertException', 'Failed asserting that array(1) contains "1"');


Assert::notContains('2', 'a1');
Assert::notContains('1', array(TRUE));

Assert::exception(function(){
	Assert::notContains('1', '1');
}, 'Tester\AssertException', 'Failed asserting that "1" not contains "1"');

Assert::exception(function(){
	Assert::notContains('1', 'a1');
}, 'Tester\AssertException', 'Failed asserting that "a1" not contains "1"');

Assert::exception(function(){
	Assert::notContains('1', array('1'));
}, 'Tester\AssertException', 'Failed asserting that array(1) not contains "1"');

Assert::exception(function(){
	Assert::notContains(1, 1);
}, 'Tester\AssertException', 'Failed asserting that 1 is string or array');
