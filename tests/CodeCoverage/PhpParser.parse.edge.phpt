<?php

use Tester\Assert;
use Tester\CodeCoverage;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/CodeCoverage/PhpParser.php';


$parser = new CodeCoverage\PhpParser;
$parsed = $parser->parse(file_get_contents(__DIR__ . '/parse/edge.php'));

Assert::equal(array(
	'foo' => (object) array(
		'start' => 3,
		'end' => 6,
	),
), $parsed->functions);

Assert::equal(array(
	'fun' => (object) array(
		'start' => 10,
		'end' => 14,
		'visibility' => 'public',
	),
), $parsed->classes['C']->methods);


if (PHP_VERSION_ID >= 50500) {
	// The '::CLASS' construct
	Assert::equal((object) array(
		'linesOfCode' => 1,
		'linesOfComments' => 0,
		'functions' => array(),
		'classes' => array(
			'A' => (object) array('start' => 1, 'end' => 1, 'methods' => array()),
			'B' => (object) array('start' => 1, 'end' => 1, 'methods' => array()),
		),
		'traits' => array(),
		'interfaces' => array(),
	), $parser->parse('<? class A {}  echo A::CLASS;  class B {}'));
}
