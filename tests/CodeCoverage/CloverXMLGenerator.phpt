<?php

use Tester\Assert;
use Tester\CodeCoverage;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/CodeCoverage/PhpParser.php';
require __DIR__ . '/../../src/CodeCoverage/Generators/AbstractGenerator.php';
require __DIR__ . '/../../src/CodeCoverage/Generators/CloverXMLGenerator.php';


$coveredDir = __DIR__ . DIRECTORY_SEPARATOR . 'clover';

$coverageData = Tester\FileMock::create(serialize(array(
	$coveredDir . DIRECTORY_SEPARATOR . 'Logger.php' => array_map('intval', preg_filter(
		'~.*# (-?\d+)~',
		'$1',
		explode("\n", "\n" . file_get_contents($coveredDir . DIRECTORY_SEPARATOR . 'Logger.php'))
	)),
)));

$generator = new CodeCoverage\Generators\CloverXMLGenerator($coverageData, $coveredDir);
$generator->render($output = Tester\FileMock::create('', 'xml'));

Assert::matchFile(__DIR__ . '/CloverXMLGenerator.expected.xml', file_get_contents($output));
