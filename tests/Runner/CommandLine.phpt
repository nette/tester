<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Runner\CommandLine as Cmd;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/CommandLine.php';



test(function () {
	$cmd = new Cmd('
		-p
		--p
		--a-b
	');

	Assert::same(['-p' => null, '--p' => null, '--a-b' => null], $cmd->parse([]));
	Assert::same(['-p' => true, '--p' => null, '--a-b' => null], $cmd->parse(['-p']));

	$cmd = new Cmd('
		-p  description
	');

	Assert::same(['-p' => null], $cmd->parse([]));
	Assert::same(['-p' => true], $cmd->parse(['-p']));
});


test(function () { // default value
	$cmd = new Cmd('
		-p  (default: 123)
	');

	Assert::same(['-p' => '123'], $cmd->parse([]));
	Assert::same(['-p' => true], $cmd->parse(['-p']));


	$cmd = new Cmd('
		-p
	', [
		'-p' => [Cmd::Value => 123],
	]);

	Assert::same(['-p' => 123], $cmd->parse([]));
	Assert::same(['-p' => true], $cmd->parse(['-p']));
});


test(function () { // alias
	$cmd = new Cmd('
		-p | --param
	');

	Assert::same(['--param' => null], $cmd->parse([]));
	Assert::same(['--param' => true], $cmd->parse(['-p']));
	Assert::same(['--param' => true], $cmd->parse(['--param']));
	Assert::same(['--param' => true], $cmd->parse(explode(' ', '-p --param')));
	Assert::exception(function () use ($cmd) {
		$cmd->parse(['-p=val']);
	}, Exception::class, 'Option --param has not argument.');

	$cmd = new Cmd('
		-p --param
	');

	Assert::same(['--param' => true], $cmd->parse(['-p']));

	$cmd = new Cmd('
		-p, --param
	');

	Assert::same(['--param' => true], $cmd->parse(['-p']));
});


test(function () { // argument
	$cmd = new Cmd('
		-p param
	');

	Assert::same(['-p' => null], $cmd->parse([]));
	Assert::same(['-p' => 'val'], $cmd->parse(explode(' ', '-p val')));
	Assert::same(['-p' => 'val'], $cmd->parse(explode(' ', '-p=val')));
	Assert::same(['-p' => 'val2'], $cmd->parse(explode(' ', '-p val1 -p val2')));

	Assert::exception(function () use ($cmd) {
		$cmd->parse(['-p']);
	}, Exception::class, 'Option -p requires argument.');

	Assert::exception(function () use ($cmd) {
		$cmd->parse(['-p', '-a']);
	}, Exception::class, 'Option -p requires argument.');


	$cmd = new Cmd('
		-p=<param>
	');

	Assert::same(['-p' => 'val'], $cmd->parse(explode(' ', '-p val')));
});


test(function () { // optional argument
	$cmd = new Cmd('
		-p [param]
	');

	Assert::same(['-p' => null], $cmd->parse([]));
	Assert::same(['-p' => true], $cmd->parse(['-p']));
	Assert::same(['-p' => 'val'], $cmd->parse(explode(' ', '-p val')));


	$cmd = new Cmd('
		-p param
	', [
		'-p' => [Cmd::Value => 123],
	]);

	Assert::same(['-p' => 123], $cmd->parse([]));
	Assert::same(['-p' => true], $cmd->parse(['-p']));
	Assert::same(['-p' => 'val'], $cmd->parse(explode(' ', '-p val')));


	$cmd = new Cmd('
		-p param
	', [
		'-p' => [Cmd::Optional => true],
	]);

	Assert::same(['-p' => null], $cmd->parse([]));
	Assert::same(['-p' => true], $cmd->parse(['-p']));
	Assert::same(['-p' => 'val'], $cmd->parse(explode(' ', '-p val')));
});


test(function () { // repeatable argument
	$cmd = new Cmd('
		-p [param]...
	');

	Assert::same(['-p' => []], $cmd->parse([]));
	Assert::same(['-p' => [true]], $cmd->parse(['-p']));
	Assert::same(['-p' => ['val']], $cmd->parse(explode(' ', '-p val')));
	Assert::same(['-p' => ['val1', 'val2']], $cmd->parse(explode(' ', '-p val1 -p val2')));
});


test(function () { // enumerates
	$cmd = new Cmd('
		-p <a|b|c>
	');

	Assert::same(['-p' => null], $cmd->parse([]));
	Assert::exception(function () use ($cmd) {
		$cmd->parse(['-p']);
	}, Exception::class, 'Option -p requires argument.');
	Assert::same(['-p' => 'a'], $cmd->parse(explode(' ', '-p a')));
	Assert::exception(function () use ($cmd) {
		$cmd->parse(explode(' ', '-p foo'));
	}, Exception::class, 'Value of option -p must be a, or b, or c.');


	$cmd = new Cmd('
		-p [a|b|c]
	');

	Assert::same(['-p' => null], $cmd->parse([]));
	Assert::same(['-p' => true], $cmd->parse(['-p']));
	Assert::same(['-p' => 'a'], $cmd->parse(explode(' ', '-p a')));
	Assert::exception(function () use ($cmd) {
		$cmd->parse(explode(' ', '-p foo'));
	}, Exception::class, 'Value of option -p must be a, or b, or c.');
});


test(function () { // realpath
	$cmd = new Cmd('
		-p <path>
	', [
		'-p' => [Cmd::Realpath => true],
	]);

	Assert::exception(function () use ($cmd) {
		$cmd->parse(['-p', 'xyz']);
	}, Exception::class, "File path 'xyz' not found.");
	Assert::same(['-p' => __FILE__], $cmd->parse(['-p', __FILE__]));
});


test(function () { // normalizer
	$cmd = new Cmd('
		-p param
	', [
		'-p' => [Cmd::Normalizer => function ($arg) {
			return "$arg-normalized";
		}],
	]);

	Assert::same(['-p' => 'val-normalized'], $cmd->parse(explode(' ', '-p val')));


	$cmd = new Cmd('
		-p <a|b>
	', [
		'-p' => [Cmd::Normalizer => function () {
			return 'a';
		}],
	]);

	Assert::same(['-p' => 'a'], $cmd->parse(explode(' ', '-p xxx')));


	$cmd = new Cmd('
		-p <a|b>
	', [
		'-p' => [Cmd::Normalizer => function () {
			return ['a', 'foo'];
		}],
	]);

	Assert::same(['-p' => ['a', 'foo']], $cmd->parse(explode(' ', '-p xxx')));
});


test(function () { // positional arguments
	$cmd = new Cmd('', [
		'pos' => [],
	]);

	Assert::same(['pos' => 'val'], $cmd->parse(['val']));

	Assert::exception(function () use ($cmd) {
		$cmd->parse([]);
	}, Exception::class, 'Missing required argument <pos>.');

	Assert::exception(function () use ($cmd) {
		$cmd->parse(['val1', 'val2']);
	}, Exception::class, 'Unexpected parameter val2.');

	$cmd = new Cmd('', [
		'pos' => [Cmd::Repeatable => true],
	]);

	Assert::same(['pos' => ['val1', 'val2']], $cmd->parse(['val1', 'val2']));


	$cmd = new Cmd('', [
		'pos' => [Cmd::Optional => true],
	]);

	Assert::same(['pos' => null], $cmd->parse([]));


	$cmd = new Cmd('', [
		'pos' => [Cmd::Value => 'default', Cmd::Repeatable => true],
	]);

	Assert::same(['pos' => ['default']], $cmd->parse([]));
});


test(function () { // errors
	$cmd = new Cmd('
		-p
	');

	Assert::exception(function () use ($cmd) {
		$cmd->parse(['-x']);
	}, Exception::class, 'Unknown option -x.');

	Assert::exception(function () use ($cmd) {
		$cmd->parse(['val']);
	}, Exception::class, 'Unexpected parameter val.');
});
