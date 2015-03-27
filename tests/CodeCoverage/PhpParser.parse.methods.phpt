<?php

use Tester\Assert;
use Tester\CodeCoverage;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/CodeCoverage/PhpParser.php';


$parser = new CodeCoverage\PhpParser;

Assert::equal(array(
	'fun' => (object) array('start' => 5, 'end' => 5, 'visibility' => 'public'),
	'pub' => (object) array('start' => 6, 'end' => 6, 'visibility' => 'public'),
	'pro' => (object) array('start' => 7, 'end' => 7, 'visibility' => 'protected'),
	'pri' => (object) array('start' => 8, 'end' => 8, 'visibility' => 'private'),

	'funS' => (object) array('start' => 10, 'end' => 10, 'visibility' => 'public'),
	'pubS' => (object) array('start' => 11, 'end' => 11, 'visibility' => 'public'),
	'proS' => (object) array('start' => 12, 'end' => 12, 'visibility' => 'protected'),
	'priS' => (object) array('start' => 13, 'end' => 13, 'visibility' => 'private'),
), $parser->parse(file_get_contents(__DIR__ . '/parse/methods.php'))->classes['C']->methods);
