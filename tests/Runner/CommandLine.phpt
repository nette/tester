<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Runner\CommandLine as Cmd;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/CommandLine.php';



test('', function () {
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


test('default value', function () {
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


test('alias', function () {
	$cmd = new Cmd('
		-p | --param
	');

	Assert::same(['--param' => null], $cmd->parse([]));
	Assert::same(['--param' => true], $cmd->parse(['-p']));
	Assert::same(['--param' => true], $cmd->parse(['--param']));
	Assert::same(['--param' => true], $cmd->parse(explode(' ', '-p --param')));
	Assert::exception(
		fn() => $cmd->parse(['-p=val']),
		Exception::class,
		'Option --param has not argument.',
	);

	$cmd = new Cmd('
		-p --param
	');

	Assert::same(['--param' => true], $cmd->parse(['-p']));

	$cmd = new Cmd('
		-p, --param
	');

	Assert::same(['--param' => true], $cmd->parse(['-p']));
});


test('argument', function () {
	$cmd = new Cmd('
		-p param
	');

	Assert::same(['-p' => null], $cmd->parse([]));
	Assert::same(['-p' => 'val'], $cmd->parse(explode(' ', '-p val')));
	Assert::same(['-p' => 'val'], $cmd->parse(explode(' ', '-p=val')));
	Assert::same(['-p' => 'val2'], $cmd->parse(explode(' ', '-p val1 -p val2')));

	Assert::exception(
		fn() => $cmd->parse(['-p']),
		Exception::class,
		'Option -p requires argument.',
	);

	Assert::exception(
		fn() => $cmd->parse(['-p', '-a']),
		Exception::class,
		'Option -p requires argument.',
	);


	$cmd = new Cmd('
		-p=<param>
	');

	Assert::same(['-p' => 'val'], $cmd->parse(explode(' ', '-p val')));
});


test('optional argument', function () {
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


test('repeatable argument', function () {
	$cmd = new Cmd('
		-p [param]...
	');

	Assert::same(['-p' => []], $cmd->parse([]));
	Assert::same(['-p' => [true]], $cmd->parse(['-p']));
	Assert::same(['-p' => ['val']], $cmd->parse(explode(' ', '-p val')));
	Assert::same(['-p' => ['val1', 'val2']], $cmd->parse(explode(' ', '-p val1 -p val2')));
});


test('enumerates', function () {
	$cmd = new Cmd('
		-p <a|b|c>
	');

	Assert::same(['-p' => null], $cmd->parse([]));
	Assert::exception(
		fn() => $cmd->parse(['-p']),
		Exception::class,
		'Option -p requires argument.',
	);
	Assert::same(['-p' => 'a'], $cmd->parse(explode(' ', '-p a')));
	Assert::exception(
		fn() => $cmd->parse(explode(' ', '-p foo')),
		Exception::class,
		'Value of option -p must be a, or b, or c.',
	);


	$cmd = new Cmd('
		-p [a|b|c]
	');

	Assert::same(['-p' => null], $cmd->parse([]));
	Assert::same(['-p' => true], $cmd->parse(['-p']));
	Assert::same(['-p' => 'a'], $cmd->parse(explode(' ', '-p a')));
	Assert::exception(
		fn() => $cmd->parse(explode(' ', '-p foo')),
		Exception::class,
		'Value of option -p must be a, or b, or c.',
	);
});


test('realpath', function () {
	$cmd = new Cmd('
		-p <path>
	', [
		'-p' => [Cmd::RealPath => true],
	]);

	Assert::exception(
		fn() => $cmd->parse(['-p', 'xyz']),
		Exception::class,
		"File path 'xyz' not found.",
	);
	Assert::same(['-p' => __FILE__], $cmd->parse(['-p', __FILE__]));
});


test('normalizer', function () {
	$cmd = new Cmd('
		-p param
	', [
		'-p' => [Cmd::Normalizer => fn($arg) => "$arg-normalized"],
	]);

	Assert::same(['-p' => 'val-normalized'], $cmd->parse(explode(' ', '-p val')));


	$cmd = new Cmd('
		-p <a|b>
	', [
		'-p' => [Cmd::Normalizer => fn() => 'a'],
	]);

	Assert::same(['-p' => 'a'], $cmd->parse(explode(' ', '-p xxx')));


	$cmd = new Cmd('
		-p <a|b>
	', [
		'-p' => [Cmd::Normalizer => fn() => ['a', 'foo']],
	]);

	Assert::same(['-p' => ['a', 'foo']], $cmd->parse(explode(' ', '-p xxx')));
});


test('positional arguments', function () {
	$cmd = new Cmd('', [
		'pos' => [],
	]);

	Assert::same(['pos' => 'val'], $cmd->parse(['val']));

	Assert::exception(
		fn() => $cmd->parse([]),
		Exception::class,
		'Missing required argument <pos>.',
	);

	Assert::exception(
		fn() => $cmd->parse(['val1', 'val2']),
		Exception::class,
		'Unexpected parameter val2.',
	);

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


test('errors', function () {
	$cmd = new Cmd('
		-p
	');

	Assert::exception(
		fn() => $cmd->parse(['-x']),
		Exception::class,
		'Unknown option -x.',
	);

	Assert::exception(
		fn() => $cmd->parse(['val']),
		Exception::class,
		'Unexpected parameter val.',
	);
});
