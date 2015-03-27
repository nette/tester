<?php

use Tester\Assert;
use Tester\CodeCoverage;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/CodeCoverage/PhpParser.php';
require __DIR__ . '/../../src/CodeCoverage/Generators/AbstractGenerator.php';
require __DIR__ . '/../../src/CodeCoverage/Generators/CloverXMLGenerator.php';


$coveredFile = __DIR__ . DIRECTORY_SEPARATOR . 'CloverXMLGenerator.covered.php';

$coverageData = Tester\FileMock::create(serialize(array(
	$coveredFile => array_map('intval', preg_filter(
		'~.*# (-?\d+)~',
		'$1',
		explode("\n", "\n" . file_get_contents($coveredFile))
	)),
)));

$generator = new CodeCoverage\Generators\CloverXMLGenerator($coverageData, $coveredFile);
$generator->render($output = Tester\FileMock::create('', 'xml'));

Assert::matchFile(__DIR__ . '/CloverXMLGenerator.expected.xml', file_get_contents($output));
