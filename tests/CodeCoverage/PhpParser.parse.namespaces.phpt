<?php

use Tester\Assert;
use Tester\CodeCoverage;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/CodeCoverage/PhpParser.php';


$parser = new CodeCoverage\PhpParser;


test(function () use ($parser) {
	$parsed = $parser->parse(file_get_contents(__DIR__ . '/parse/namespaces.none.php'));

	Assert::equal(array(
		'f' => (object) array('start' => 3, 'end' => 3),
	), $parsed->functions);

	Assert::equal(array(
		'C' => (object) array('start' => 4, 'end' => 4, 'methods' => array()),
	), $parsed->classes);

	Assert::equal(array(
		'T' => (object) array('start' => 5, 'end' => 5, 'methods' => array()),
	), $parsed->traits);

	Assert::equal(array(
		'I' => (object) array('start' => 6, 'end' => 6, 'methods' => array()),
	), $parsed->interfaces);
});


test(function () use ($parser) {
	$parsed = $parser->parse(file_get_contents(__DIR__ . '/parse/namespaces.php'));

	Assert::equal(array(
		'N\f' => (object) array('start' => 5, 'end' => 5),
		'N\S\f' => (object) array('start' => 13, 'end' => 13),
	), $parsed->functions);

	Assert::equal(array(
		'N\C' => (object) array('start' => 6, 'end' => 6, 'methods' => array()),
		'N\S\C' => (object) array('start' => 14, 'end' => 14, 'methods' => array()),
	), $parsed->classes);

	Assert::equal(array(
		'N\T' => (object) array('start' => 7, 'end' => 7, 'methods' => array()),
		'N\S\T' => (object) array('start' => 15, 'end' => 15, 'methods' => array()),
	), $parsed->traits);

	Assert::equal(array(
		'N\I' => (object) array('start' => 8, 'end' => 8, 'methods' => array()),
		'N\S\I' => (object) array('start' => 16, 'end' => 16, 'methods' => array()),
	), $parsed->interfaces);
});


test(function () use ($parser) {
	$parsed = $parser->parse(file_get_contents(__DIR__ . '/parse/namespaces.braces.php'));

	Assert::equal(array(
		'f' => (object) array('start' => 4, 'end' => 4),
		'N\f' => (object) array('start' => 11, 'end' => 11),
		'N\S\f' => (object) array('start' => 18, 'end' => 18),
	), $parsed->functions);

	Assert::equal(array(
		'C' => (object) array('start' => 5, 'end' => 5, 'methods' => array()),
		'N\C' => (object) array('start' => 12, 'end' => 12, 'methods' => array()),
		'N\S\C' => (object) array('start' => 19, 'end' => 19, 'methods' => array()),
	), $parsed->classes);

	Assert::equal(array(
		'T' => (object) array('start' => 6, 'end' => 6, 'methods' => array()),
		'N\T' => (object) array('start' => 13, 'end' => 13, 'methods' => array()),
		'N\S\T' => (object) array('start' => 20, 'end' => 20, 'methods' => array()),
	), $parsed->traits);

	Assert::equal(array(
		'I' => (object) array('start' => 7, 'end' => 7, 'methods' => array()),
		'N\I' => (object) array('start' => 14, 'end' => 14, 'methods' => array()),
		'N\S\I' => (object) array('start' => 21, 'end' => 21, 'methods' => array()),
	), $parsed->interfaces);
});
