<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Helpers;

require __DIR__ . '/../bootstrap.php';


Assert::same([], Helpers::parseDocComment(''));
Assert::same([], Helpers::parseDocComment('/** */'));
Assert::same([], Helpers::parseDocComment("/**\n*/ "));
Assert::same([], Helpers::parseDocComment(' /** */ '));
Assert::same([], Helpers::parseDocComment(' /**  Hello world */ '));
Assert::same(['Hello world'], Helpers::parseDocComment('/**  Hello world */ '));
Assert::same(['var' => ''], Helpers::parseDocComment('/**  @var  */ '));
Assert::same(['var' => 'a b'], Helpers::parseDocComment('/** @var  a b */ '));
Assert::same([
	'Hello world',
	'var' => ['', 'b'],
], Helpers::parseDocComment('/**
 *	Hello world
	@var
 *	@var b
 */ '));
