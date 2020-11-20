<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\FileMock;

require __DIR__ . '/../bootstrap.php';


Assert::notContains('mock', stream_get_wrappers());
FileMock::create('');
Assert::contains('mock', stream_get_wrappers());


// Opening non-existing
test(function () {
	$cases = [
		'r' => $tmp = [
			[E_USER_WARNING, 'fopen(mock://none): failed to open stream: No such file or directory'],
			[E_WARNING, 'fopen(mock://none): %[fF]%ailed to open stream: "Tester\FileMock::stream_open" call failed'],
		],
		'r+' => $tmp,
		'w' => [],
		'w+' => [],
		'a' => [],
		'a+' => [],
		'x' => [],
		'x+' => [],
		'c' => [],
		'c+' => [],
	];

	foreach ($cases as $mode => $errors) {
		FileMock::$files = [];

		Assert::error(function () use ($mode) {
			fopen('mock://none', $mode);
		}, $errors);
		Assert::count(count($errors) ? 0 : 1, FileMock::$files);
	}
});


// Opening existing
test(function () {
	FileMock::$files = [];

	$cases = [
		'r' => [],
		'r+' => [],
		'w' => [],
		'w+' => [],
		'a' => [],
		'a+' => [],
		'x' => $tmp = [
			[E_USER_WARNING, 'fopen(mock://%i%.): failed to open stream: File exists'],
			[E_WARNING, 'fopen(mock://%i%.): %[fF]%ailed to open stream: "Tester\FileMock::stream_open" call failed'],
		],
		'x+' => $tmp,
		'c' => [],
		'c+' => [],
	];

	foreach ($cases as $mode => $errors) {
		Assert::error(function () use ($mode) {
			fopen(FileMock::create(''), $mode);
		}, $errors);
	}
});


// Initial cursor position
test(function () {
	FileMock::$files = [];

	$cases = [
		'r' => 0,
		'r+' => 0,
		'w' => 0,
		'w+' => 0,
		'a' => 0,
		'a+' => 0,
		'x' => 0,
		'x+' => 0,
		'c' => 0,
		'c+' => 0,
	];

	foreach ($cases as $mode => $position) {
		$file = $mode[0] === 'x'
			? "mock://none-$mode"
			: FileMock::create('ABC');
		Assert::same($position, ftell(fopen($file, $mode)), "Mode $mode");
	}
});


// Truncation on open
test(function () {
	FileMock::$files = [];

	$cases = [
		'r' => ['ABC', 'ABC'],
		'r+' => ['ABC', 'ABC'],
		'w' => ['', PHP_VERSION_ID < 70400 ? '' : false],
		'w+' => ['', ''],
		'a' => ['ABC', PHP_VERSION_ID < 70400 ? '' : false],
		'a+' => ['ABC', 'ABC'],
		'x' => ['', PHP_VERSION_ID < 70400 ? '' : false],
		'x+' => ['', ''],
		'c' => ['ABC', PHP_VERSION_ID < 70400 ? '' : false],
		'c+' => ['ABC', 'ABC'],
	];

	foreach ($cases as $mode => [$contents, $readOut]) {
		$file = $mode[0] === 'x'
			? "mock://none-$mode"
			: FileMock::create('ABC');

		$f = fopen($file, $mode);
		fseek($f, 0);

		Assert::same($contents, FileMock::$files[$file], "Mode $mode");
		Assert::same($readOut, fread($f, 512), "Mode $mode");
	}
});

// Writing position after open
test(function () {
	FileMock::$files = [];

	$cases = [
		'r' => ['ABC', 'ABC'],
		'r+' => ['_BC', '_BC'],
		'w' => ['_', PHP_VERSION_ID < 70400 ? '' : false],
		'w+' => ['_', '_'],
		'a' => ['ABC_', PHP_VERSION_ID < 70400 ? '' : false],
		'a+' => ['ABC_', 'ABC_'],
		'x' => ['_', PHP_VERSION_ID < 70400 ? '' : false],
		'x+' => ['_', '_'],
		'c' => ['_BC', PHP_VERSION_ID < 70400 ? '' : false],
		'c+' => ['_BC', '_BC'],
	];

	foreach ($cases as $mode => [$contents, $readOut]) {
		$file = $mode[0] === 'x'
			? "mock://none-$mode"
			: FileMock::create('ABC');

		$f = fopen($file, $mode);
		fwrite($f, '_');
		fseek($f, 0);

		Assert::same($contents, FileMock::$files[$file], "Mode $mode");
		Assert::same($readOut, fread($f, 512), "Mode $mode");
	}
});


// Filesystem functions
test(function () {
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

	Assert::true(ftruncate($f, 5));
	Assert::same(15, ftell($f));
	Assert::same('hello', file_get_contents($name));

	fclose($f);
});


// Unlink
test(function () {
	fopen($name = Tester\FileMock::create('foo'), 'r');
	Assert::true(unlink($name));
	Assert::false(@unlink($name));
	Assert::error(function () use ($name) {
		unlink($name);
	}, E_USER_WARNING, "unlink($name): No such file");
});


// Runtime include
test(function () {
	Assert::same(123, require FileMock::create('<?php return 123;'));
});


// Locking
test(function () {
	Assert::false(flock(fopen(FileMock::create(''), 'w'), LOCK_EX));
});


// Position handling across modes
test(function () {
	$modes = ['r', 'r+', 'w', 'w+', 'a', 'a+', 'c', 'c+'];
	$pathReal = __DIR__ . '/real-file.txt';

	foreach ($modes as $mode) {
		file_put_contents($pathReal, 'Hello');
		$pathMock = FileMock::create('Hello');

		$handleReal = fopen($pathReal, $mode);
		$handleMock = fopen($pathMock, $mode);
		Assert::same(ftell($handleReal), ftell($handleMock));
		Assert::same(file_get_contents($pathReal), file_get_contents($pathMock));

		Assert::same(@fwrite($handleReal, 'World'), fwrite($handleMock, 'World')); // @ - triggers E_NOTICE since PHP 7.4
		Assert::same(ftell($handleReal), ftell($handleMock));
		Assert::same(file_get_contents($pathReal), file_get_contents($pathMock));

		Assert::same(ftruncate($handleReal, 2), ftruncate($handleMock, 2));
		Assert::same(ftell($handleReal), ftell($handleMock));
		Assert::same(file_get_contents($pathReal), file_get_contents($pathMock));

		Assert::same(@fwrite($handleReal, 'World'), fwrite($handleMock, 'World')); // @ - triggers E_NOTICE since PHP 7.4
		Assert::same(ftell($handleReal), ftell($handleMock));
		Assert::same(file_get_contents($pathReal), file_get_contents($pathMock));

		Assert::same(fseek($handleReal, 2), fseek($handleMock, 2));
		Assert::same(@fread($handleReal, 7), fread($handleMock, 7)); // @ - triggers E_NOTICE since PHP 7.4
		Assert::same(fclose($handleReal), fclose($handleMock));
	}

	unlink($pathReal);
});


// touch
test(function () {
	fopen($name = Tester\FileMock::create('foo'), 'r');
	Assert::true(touch($name));
});
