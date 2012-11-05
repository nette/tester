<?php

require __DIR__ . '/bootstrap.php';


Assert::same(1, 1);
Assert::same('1', '1');
Assert::same(array('1'), array('1'));
Assert::same($obj = new stdClass, $obj);

Assert::exception(function(){
	Assert::same(1, 1.0);
}, 'AssertException', 'Failed asserting that 1.0 is identical to expected 1');

Assert::exception(function(){
	Assert::same(array('a' => true, 'b' => false), array('b' => false, 'a' => true));
}, 'AssertException', 'Failed asserting that array(2) is identical to expected array(2)');

Assert::exception(function(){
	Assert::same(new stdClass, new stdClass);
}, 'AssertException', 'Failed asserting that object(stdClass) (0) is identical to expected object(stdClass) (0)');
