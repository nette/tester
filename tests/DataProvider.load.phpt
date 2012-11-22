<?php

use Tester\Assert,
	Tester\DataProvider;

require __DIR__ . '/bootstrap.php';


Assert::same( array(
	'foo' => array(),
	'bar' => array(),
), DataProvider::load('fixtures/dataprovider.ini') );

Assert::same( array(
	'bar 1.2.3' => array('a' => '1'),
	'bar' => array('b' => '2'),
), DataProvider::load('fixtures/dataprovider.query.ini', ' = bar') );
