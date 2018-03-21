<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$matches = [
	['1', '1'],
	['1', 1],
	["a\nb", "a\r\nb"],
	["a\r\nb", "a\nb"],
	["a\t \nb", "a\nb"],
	["a\nb", "a\t \nb"],
	["a\t\r\n\t ", 'a'],
	['a', "a\t\r\n\t "],
	["\x00", "\x00"],
	['%%', '%'],
	['%%a%%', '%a%'],
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
	['%h%', 'aBcDeF01'],
	['%w%', 'aBzZ_01'],
	['%ds%%ds%', '\\/'],
	['%[a-c]+%', 'abc'],
	['%[]%', '%[]%'],
	['.\\+*?[^]$(){}=!<>|:-#', '.\\+*?[^]$(){}=!<>|:-#'],
	['~\d+~', '123'],
	['#\d+#', '123'],
];

$notMatches = [
	['', 'a', '', 'a'],
	['a', ' a ', 'a', ' a'],
	["a\nb", "a\r\nx", "a\nb", "a\nx"],
	["a\r\nb", "a\nx", "a\nb", "a\nx"],
	["a\t \nb", "a\nx", "a\nb", "a\nx"],
	["a\nb", "a\t \nx", "a\nb", "a\nx"],
	["a\t\r\n\t ", 'x', 'a', 'x'],
	['a', "x\t\r\n\t ", 'a', 'x'],
	['%a%', "a\nb", 'a', "a\nb"],
	['%a%', '', '%a%', ''],
	['%A%', '', '%A%', ''],
	['a%s%b', "a\nb", 'a%s%b', "a\nb"],
	['%s?%', 'a', '', 'a'],
	['a%c%c', 'abbc', 'abc', 'abbc'],
	['a%c%c', 'ac', 'acc', 'ac'],
	['a%c%c', "a\nc", 'a%c%c', "a\nc"],
	['%d%', '', '%d%', ''],
	['%i%', '-123.5', '-123', '-123.5'],
	['%i%', '', '%i%', ''],
	['%f%', '', '%f%', ''],
	['%h%', 'gh', '%h%', 'gh'],
	['%h%', '', '%h%', ''],
	['%w%', ',', '%w%', ','],
	['%w%', '', '%w%', ''],
	['%[a-c]+%', 'Abc', '%[a-c]+%', 'Abc'],
	['foo%d%foo', 'foo123baz', 'foo123foo', 'foo123baz'],
	['foo%d%bar', 'foo123baz', 'foo123bar', 'foo123baz'],
	['foo%d?%foo', 'foo123baz', 'foo123foo', 'foo123baz'],
	['foo%d?%bar', 'foo123baz', 'foo123bar', 'foo123baz'],
	['%a%x', 'abc', 'abcx', 'abc'],
	['~%d%~', '~123~', '~%d%~', '~123~'],
];

foreach ($matches as [$expected, $actual]) {
	Assert::match($expected, $actual);
}

foreach ($notMatches as [$expected, $actual, $expected2, $actual2]) {
	$expected3 = str_replace('%', '%%', $expected2);
	$actual3 = str_replace('%', '%%', $actual2);

	$ex = Assert::exception(function () use ($expected, $actual) {
		Assert::match($expected, $actual);
	}, Tester\AssertException::class, "'$actual3' should match '$expected3'");

	Assert::same($expected2, $ex->expected);
	Assert::same($actual2, $ex->actual);
}


Assert::same('', Assert::expandMatchingPatterns('', '')[0]);
Assert::same('abc', Assert::expandMatchingPatterns('abc', 'a')[0]);
Assert::same('a', Assert::expandMatchingPatterns('%a?%', 'a')[0]);
Assert::same('123a', Assert::expandMatchingPatterns('%d?%a', '123b')[0]);
Assert::same('a', Assert::expandMatchingPatterns('a', 'a')[0]);
Assert::same('ab', Assert::expandMatchingPatterns('ab', 'abc')[0]);
Assert::same('abcx', Assert::expandMatchingPatterns('%a%x', 'abc')[0]);
Assert::same('a123c', Assert::expandMatchingPatterns('a%d%c', 'a123x')[0]);
Assert::same('a%A%b', Assert::expandMatchingPatterns('a%A%b', 'axc')[0]);


Assert::exception(function () {
	Assert::match(null, '');
}, TypeError::class);


Assert::matchFile(__DIR__ . '/Assert.matchFile.txt', '! Hello !');

Assert::exception(function () {
	Assert::match('a', 'b', 'Custom description');
}, Tester\AssertException::class, 'Custom description: %A% should match %A%');

Assert::exception(function () {
	Assert::matchFile(__DIR__ . '/Assert.matchFile.txt', '! Not match !', 'Custom description');
}, Tester\AssertException::class, 'Custom description: %A% should match %A%');
