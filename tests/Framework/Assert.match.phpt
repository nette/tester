<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$matches = [
	['1', '1'],
	['1', 1],
	['a', "a  \t\r\n\t \n"],
	["a \t\r\n", 'a'],
	['%a%', 'a b'],
	['%a?%', 'a b'],
	['%a?%', ''],
	['%A%', "a\nb"],
	['%A?%', "a\nb"],
	['%A?%', ''],
	['%s%', " \t"],
	['%s?%', " \t"],
	['%s?%', ''],
	['a%c%c', 'abc'],
	['a%c%c', 'a c'],
	['%d%', '123'],
	['%d?%', '123'],
	['%d?%', ''],
	['%i%', '-123'],
	['%i%', '+123'],
	['%f%', '-123'],
	['%f%', '+123.5'],
	['%f%', '-1e5'],
	['%h%', 'aBcDeF'],
	['%ds%%ds%', '\\/'],
	['%[a-c]+%', 'abc'],
	['%[]%', '%[]%'],
	['.\\+*?[^]$(){}=!<>|:-#', '.\\+*?[^]$(){}=!<>|:-#'],
];

$notMatches = [
	['a', ' a '],
	['%a%', "a\nb"],
	['%a%', ''],
	['%A%', ''],
	['a%s%b', "a\nb"],
	['%s?%', 'a'],
	['a%c%c', 'abbc'],
	['a%c%c', 'ac'],
	['a%c%c', "a\nc"],
	['%d%', ''],
	['%i%', '-123.5'],
	['%i%', ''],
	['%f%', ''],
	['%h%', 'gh'],
	['%h%', ''],
	['%[a-c]+%', 'Abc'],
];

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

Assert::exception(function () {
	Assert::match('a', 'b', 'Custom description');
}, 'Tester\AssertException', 'Custom description: %A% should match %A%');

Assert::exception(function () {
	Assert::matchFile(__DIR__ . '/Assert.matchFile.txt', '! Not match !', 'Custom description');
}, 'Tester\AssertException', 'Custom description: %A% should match %A%');
