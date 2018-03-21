<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\CodeCoverage;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/CodeCoverage/PhpParser.php';


$parser = new CodeCoverage\PhpParser;

Assert::equal([
	'fun' => (object) ['start' => 5, 'end' => 5, 'visibility' => 'public'],
	'pub' => (object) ['start' => 6, 'end' => 6, 'visibility' => 'public'],
	'pro' => (object) ['start' => 7, 'end' => 7, 'visibility' => 'protected'],
	'pri' => (object) ['start' => 8, 'end' => 8, 'visibility' => 'private'],

	'funS' => (object) ['start' => 10, 'end' => 10, 'visibility' => 'public'],
	'pubS' => (object) ['start' => 11, 'end' => 11, 'visibility' => 'public'],
	'proS' => (object) ['start' => 12, 'end' => 12, 'visibility' => 'protected'],
	'priS' => (object) ['start' => 13, 'end' => 13, 'visibility' => 'private'],
], $parser->parse(file_get_contents(__DIR__ . '/fixtures.parse/methods.php'))->classes['C']->methods);

Assert::equal([], $parser->parse(file_get_contents(__DIR__ . '/fixtures.parse/methods.php'))->classes['A']->methods);
