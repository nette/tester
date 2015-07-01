<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$matches = array(
	array('1', '1'),
	array('1', 1),
	array('a', "a  \t\r\n\t \n"),
	array("a \t\r\n", 'a'),
	array('%a%', 'a b'),
	array('%a?%', 'a b'),
	array('%a?%', ''),
	array('%A%', "a\nb"),
	array('%A?%', "a\nb"),
	array('%A?%', ''),
	array('%s%', " \t"),
	array('%s?%', " \t"),
	array('%s?%', ''),
	array('a%c%c', 'abc'),
	array('a%c%c', 'a c'),
	array('%d%', '123'),
	array('%d?%', '123'),
	array('%d?%', ''),
	array('%i%', '-123'),
	array('%i%', '+123'),
	array('%f%', '-123'),
	array('%f%', '+123.5'),
	array('%f%', '-1e5'),
	array('%h%', 'aBcDeF'),
	array('%ds%%ds%', '\\/'),
	array('%[a-c]+%', 'abc'),
	array('%[]%', '%[]%'),
	array('.\\+*?[^]$(){}=!<>|:-#', '.\\+*?[^]$(){}=!<>|:-#'),
);

$notMatches = array(
	array('a', ' a '),
	array('%a%', "a\nb"),
	array('%a%', ''),
	array('%A%', ''),
	array('a%s%b', "a\nb"),
	array('%s?%', 'a'),
	array('a%c%c', 'abbc'),
	array('a%c%c', 'ac'),
	array('a%c%c', "a\nc"),
	array('%d%', ''),
	array('%i%', '-123.5'),
	array('%i%', ''),
	array('%f%', ''),
	array('%h%', 'gh'),
	array('%h%', ''),
	array('%[a-c]+%', 'Abc'),
);

foreach ($matches as $case) {
	list($expected, $value) = $case;
	Assert::match($expected, $value);
}

foreach ($notMatches as $case) {
	list($expected, $value) = $case;
	Assert::exception(function () use ($expected, $value) {
		Assert::match($expected, $value);
	}, 'Tester\AssertException', '%A% should match %A%');
}

Assert::exception(function () {
	Assert::match(NULL, '');
}, 'Exception', 'Pattern must be a string.');


Assert::matchFile(__DIR__ . '/Assert.matchFile.txt', '! Hello !');
