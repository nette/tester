<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\CodeCoverage;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/CodeCoverage/PhpParser.php';


$parser = new CodeCoverage\PhpParser;
$parsed = $parser->parse(file_get_contents(__DIR__ . '/fixtures.parse/edge.php'));

Assert::equal([
	'foo' => (object) [
		'start' => 3,
		'end' => 6,
	],
], $parsed->functions);

Assert::equal([
	'fun' => (object) [
		'start' => 10,
		'end' => 14,
		'visibility' => 'public',
	],
], $parsed->classes['C']->methods);


// The '::class' construct
Assert::equal((object) [
	'linesOfCode' => 1,
	'linesOfComments' => 0,
	'functions' => [],
	'classes' => [
		'A' => (object) ['start' => 1, 'end' => 1, 'methods' => []],
		'B' => (object) ['start' => 1, 'end' => 1, 'methods' => []],
	],
	'traits' => [],
	'interfaces' => [],
], $parser->parse('<?php class A {}  echo A::class;  class B {}'));
