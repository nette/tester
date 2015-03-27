<?php

use Tester\Assert;
use Tester\CodeCoverage;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/CodeCoverage/PhpParser.php';


$parser = new CodeCoverage\PhpParser;

Assert::equal((object) array(
	'start' => 3,
	'end' => 43,
	'methods' => array(
		'fWhitespace' => (object) array('start' => 6, 'end' => 9, 'visibility' => 'public'),
		'fSingle' => (object) array('start' => 11, 'end' => 14, 'visibility' => 'public'),
		'fDouble' => (object) array('start' => 16, 'end' => 19, 'visibility' => 'public'),
		'fHeredoc' => (object) array('start' => 21, 'end' => 25, 'visibility' => 'public'),
		'fNowdoc' => (object) array('start' => 27, 'end' => 31, 'visibility' => 'public'),
		'fComment' => (object) array('start' => 33, 'end' => 36, 'visibility' => 'public'),
		'fDoc' => (object) array('start' => 38, 'end' => 41, 'visibility' => 'public'),
	),
), $parser->parse(file_get_contents(__DIR__ . '/parse/lines.php'))->classes['C']);
