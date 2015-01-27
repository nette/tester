<?php

use Tester\Assert,
	Tester\FileMock;

require __DIR__ . '/../bootstrap.php';


test(function() {
	$f = fopen($name = FileMock::create('', 'txt'), 'w+');

	Assert::match('%a%.txt', $name);
	Assert::true(is_file($name));
	Assert::true(file_exists($name));
	Assert::false(is_dir($name));
	Assert::true(is_readable($name));
	Assert::true(is_writable($name));
	Assert::false(is_file($name . 'unknown'));
	Assert::same(0, filesize($name));
	Assert::true(feof($f));

	Assert::same(5, fwrite($f, 'hello'));
	Assert::same(6, fwrite($f, ' world'));
	Assert::same('hello world', file_get_contents($name));

	Assert::true(feof($f));
	Assert::same(11, ftell($f));

	Assert::same(0, fseek($f, 1));
	Assert::false(feof($f));
	Assert::same(1, ftell($f));

	Assert::same(0, fseek($f, -1, SEEK_END));
	Assert::false(feof($f));
	Assert::same(10, ftell($f));

	Assert::same(0, fseek($f, -1, SEEK_CUR));
	Assert::same(9, ftell($f));

	Assert::same('l', fread($f, 1));
	Assert::same('d', fread($f, 1000));
	Assert::same(11, ftell($f));

	Assert::same(0, fseek($f, 3, SEEK_END));
	Assert::same(1, fwrite($f, '!'));
	Assert::same("hello world\x00\x00\x00!", file_get_contents($name));

	if (PHP_VERSION_ID >= 50400) {
		Assert::true(ftruncate($f, 5));
		Assert::same(15, ftell($f));
		Assert::same('hello', file_get_contents($name));
	}

	fclose($f);
});


test(function() {
	$f = fopen($name = Tester\FileMock::create('A'), 'a');
	fwrite($f, 'B');
	Assert::same('AB', file_get_contents($name));
});


test(function() {
	Assert::same(123, require FileMock::create('<?php return 123;'));
});
