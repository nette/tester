<?php

use Tester\Assert,
	Tester\Helpers;

require __DIR__ . '/bootstrap.php';


Assert::same( array(), Helpers::parseDocComment('') );
Assert::same( array(), Helpers::parseDocComment('/** */') );
Assert::same( array(), Helpers::parseDocComment("/**\n*/ ") );
Assert::same( array(), Helpers::parseDocComment(' /** */ ') );
Assert::same( array(), Helpers::parseDocComment(' /**  Hello world */ ') );
Assert::same( array('Hello world'), Helpers::parseDocComment('/**  Hello world */ ') );
Assert::same( array('var' => TRUE), Helpers::parseDocComment('/**  @var  */ ') );
Assert::same( array('var' => 'a b'), Helpers::parseDocComment('/** @var  a b */ ') );
Assert::same( array(
	'Hello world',
	'var' => array(TRUE, 'b'),
), Helpers::parseDocComment('/**
 *	Hello world
	@var
 *	@var b
 */ ') );
