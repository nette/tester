<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\CodeCoverage;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/CodeCoverage/PhpParser.php';


$parser = new CodeCoverage\PhpParser;

Assert::equal((object) [
	'start' => 3,
	'end' => 43,
	'methods' => [
		'fWhitespace' => (object) ['start' => 6, 'end' => 9, 'visibility' => 'public'],
		'fSingle' => (object) ['start' => 11, 'end' => 14, 'visibility' => 'public'],
		'fDouble' => (object) ['start' => 16, 'end' => 19, 'visibility' => 'public'],
		'fHeredoc' => (object) ['start' => 21, 'end' => 25, 'visibility' => 'public'],
		'fNowdoc' => (object) ['start' => 27, 'end' => 31, 'visibility' => 'public'],
		'fComment' => (object) ['start' => 33, 'end' => 36, 'visibility' => 'public'],
		'fDoc' => (object) ['start' => 38, 'end' => 41, 'visibility' => 'public'],
	],
], $parser->parse(file_get_contents(__DIR__ . '/fixtures.parse/lines.php'))->classes['C']);
