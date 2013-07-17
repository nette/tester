<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::contains('1', '1');
Assert::contains('1', 'a1');
Assert::contains('1', array('1'));

Assert::exception(function(){
	Assert::contains(1, 1);
}, 'Tester\AssertException', '1 should be string or array');

Assert::exception(function(){
	Assert::contains('2', 'a1');
}, 'Tester\AssertException', '"a1" should contain "2"');

Assert::exception(function(){
	Assert::contains('1', array(TRUE));
}, 'Tester\AssertException', 'array(1) should contain "1"');


Assert::notContains('2', 'a1');
Assert::notContains('1', array(TRUE));

Assert::exception(function(){
	Assert::notContains('1', '1');
}, 'Tester\AssertException', '"1" should not contain "1"');

Assert::exception(function(){
	Assert::notContains('1', 'a1');
}, 'Tester\AssertException', '"a1" should not contain "1"');

Assert::exception(function(){
	Assert::notContains('1', array('1'));
}, 'Tester\AssertException', 'array(1) should not contain "1"');

Assert::exception(function(){
	Assert::notContains(1, 1);
}, 'Tester\AssertException', '1 should be string or array');
