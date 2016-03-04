<?php

use Tester\Assert;
use Tester\FileMock;

require __DIR__ . '/../bootstrap.php';


Assert::notContains('mock', stream_get_wrappers());
FileMock::create('');
Assert::contains('mock', stream_get_wrappers());


// Opening non-existing
test(function () {
	$cases = array(
		'r'  => $tmp = array(
			array(E_USER_WARNING, 'fopen(mock://none): failed to open stream: No such file or directory'),
			array(E_WARNING, 'fopen(mock://none): failed to open stream: "Tester\FileMock::stream_open" call failed'),
		),
		'r+' => $tmp,
		'w'  => array(),
		'w+' => array(),
		'a'  => array(),
		'a+' => array(),
		'x'  => array(),
		'x+' => array(),
		'c'  => array(),
		'c+' => array(),
	);

	foreach ($cases as $mode => $errors) {
		FileMock::$files = array();

		Assert::error(function () use ($mode) {
			fopen('mock://none', $mode);
		}, $errors);
		Assert::count(count($errors) ? 0 : 1, FileMock::$files);
	}
});


// Opening existing
test(function () {
	FileMock::$files = array();

	$cases = array(
		'r'  => array(),
		'r+' => array(),
		'w'  => array(),
		'w+' => array(),
		'a'  => array(),
		'a+' => array(),
		'x'  => $tmp = array(
			array(E_USER_WARNING, 'fopen(mock://%i%.): failed to open stream: File exists'),
			array(E_WARNING, 'fopen(mock://%i%.): failed to open stream: "Tester\FileMock::stream_open" call failed'),
		),
		'x+' => $tmp,
		'c'  => array(),
		'c+' => array(),
	);

	foreach ($cases as $mode => $errors) {
		Assert::error(function () use ($mode) {
			fopen(FileMock::create(''), $mode);
		}, $errors);
	}
});


// Initial cursor position
test(function () {
	FileMock::$files = array();

	$cases = array(
		'r'  => 0,
		'r+' => 0,
		'w'  => 0,
		'w+' => 0,
		'a'  => 3,
		'a+' => 3,
		'x'  => 0,
		'x+' => 0,
		'c'  => 0,
		'c+' => 0,
	);

	foreach ($cases as $mode => $position) {
		$file = $mode[0] === 'x' ? "mock://none-$mode" : FileMock::create('ABC');
		Assert::same($position, ftell(fopen($file, $mode)), "Mode $mode");
	}
});


// Truncation on open
test(function () {
	FileMock::$files = array();

	$cases = array(
		'r'  => array('ABC', 'ABC'),
		'r+' => array('ABC', 'ABC'),
		'w'  => array('', ''),
		'w+' => array('', ''),
		'a'  => array('ABC', ''),
		'a+' => array('ABC', 'ABC'),
		'x'  => array('', ''),
		'x+' => array('', ''),
		'c'  => array('ABC', ''),
		'c+' => array('ABC', 'ABC'),
	);

	foreach ($cases as $mode => $case) {
		list($contents, $readOut) = $case;
		$file = $mode[0] === 'x' ? "mock://none-$mode" : FileMock::create('ABC');

		$f = fopen($file, $mode);
		fseek($f, 0);

		Assert::same($contents, FileMock::$files[$file], "Mode $mode");
		Assert::same($readOut, fread($f, 512), "Mode $mode");
	}
});

// Writing position after open
test(function () {
	FileMock::$files = array();

	$cases = array(
		'r'  => array('ABC', 'ABC'),
		'r+' => array('_BC', '_BC'),
		'w'  => array('_', ''),
		'w+' => array('_', '_'),
		'a'  => array('ABC_', ''),
		'a+' => array('ABC_', 'ABC_'),
		'x'  => array('_', ''),
		'x+' => array('_', '_'),
		'c'  => array('_BC', ''),
		'c+' => array('_BC', '_BC'),
	);

	foreach ($cases as $mode => $case) {
		list($contents, $readOut) = $case;
		$file = $mode[0] === 'x' ? "mock://none-$mode" : FileMock::create('ABC');

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

	if (PHP_VERSION_ID >= 50400) {
		Assert::true(ftruncate($f, 5));
		Assert::same(15, ftell($f));
		Assert::same('hello', file_get_contents($name));
	}

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
