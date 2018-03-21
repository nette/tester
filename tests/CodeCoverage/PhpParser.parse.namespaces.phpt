<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\CodeCoverage;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/CodeCoverage/PhpParser.php';


$parser = new CodeCoverage\PhpParser;


test(function () use ($parser) {
	$parsed = $parser->parse(file_get_contents(__DIR__ . '/fixtures.parse/namespaces.none.php'));

	Assert::equal([
		'f' => (object) ['start' => 3, 'end' => 3],
	], $parsed->functions);

	Assert::equal([
		'C' => (object) ['start' => 4, 'end' => 4, 'methods' => []],
	], $parsed->classes);

	Assert::equal([
		'T' => (object) ['start' => 5, 'end' => 5, 'methods' => []],
	], $parsed->traits);

	Assert::equal([
		'I' => (object) ['start' => 6, 'end' => 6, 'methods' => []],
	], $parsed->interfaces);
});


test(function () use ($parser) {
	$parsed = $parser->parse(file_get_contents(__DIR__ . '/fixtures.parse/namespaces.php'));

	Assert::equal([
		'N\f' => (object) ['start' => 5, 'end' => 5],
		'N\S\f' => (object) ['start' => 13, 'end' => 13],
	], $parsed->functions);

	Assert::equal([
		'N\C' => (object) ['start' => 6, 'end' => 6, 'methods' => []],
		'N\S\C' => (object) ['start' => 14, 'end' => 14, 'methods' => []],
	], $parsed->classes);

	Assert::equal([
		'N\T' => (object) ['start' => 7, 'end' => 7, 'methods' => []],
		'N\S\T' => (object) ['start' => 15, 'end' => 15, 'methods' => []],
	], $parsed->traits);

	Assert::equal([
		'N\I' => (object) ['start' => 8, 'end' => 8, 'methods' => []],
		'N\S\I' => (object) ['start' => 16, 'end' => 16, 'methods' => []],
	], $parsed->interfaces);
});


test(function () use ($parser) {
	$parsed = $parser->parse(file_get_contents(__DIR__ . '/fixtures.parse/namespaces.braces.php'));

	Assert::equal([
		'f' => (object) ['start' => 4, 'end' => 4],
		'N\f' => (object) ['start' => 11, 'end' => 11],
		'N\S\f' => (object) ['start' => 18, 'end' => 18],
	], $parsed->functions);

	Assert::equal([
		'C' => (object) ['start' => 5, 'end' => 5, 'methods' => []],
		'N\C' => (object) ['start' => 12, 'end' => 12, 'methods' => []],
		'N\S\C' => (object) ['start' => 19, 'end' => 19, 'methods' => []],
	], $parsed->classes);

	Assert::equal([
		'T' => (object) ['start' => 6, 'end' => 6, 'methods' => []],
		'N\T' => (object) ['start' => 13, 'end' => 13, 'methods' => []],
		'N\S\T' => (object) ['start' => 20, 'end' => 20, 'methods' => []],
	], $parsed->traits);

	Assert::equal([
		'I' => (object) ['start' => 7, 'end' => 7, 'methods' => []],
		'N\I' => (object) ['start' => 14, 'end' => 14, 'methods' => []],
		'N\S\I' => (object) ['start' => 21, 'end' => 21, 'methods' => []],
	], $parsed->interfaces);
});
