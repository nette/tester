<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\CodeCoverage;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/CodeCoverage/PhpParser.php';
require __DIR__ . '/../../src/CodeCoverage/Generators/AbstractGenerator.php';
require __DIR__ . '/../../src/CodeCoverage/Generators/CloverXMLGenerator.php';


$coveredDir = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures.clover';

$coverageData = Tester\FileMock::create(serialize([
	$coveredDir . DIRECTORY_SEPARATOR . 'Logger.php' => array_map('intval', preg_filter(
		'~.*# (-?\d+)~',
		'$1',
		explode("\n", "\n" . file_get_contents($coveredDir . DIRECTORY_SEPARATOR . 'Logger.php')),
	)),
]));

$generator = new CodeCoverage\Generators\CloverXMLGenerator($coverageData, [$coveredDir]);
$generator->render($output = Tester\FileMock::create('', 'xml'));

$dom = new DOMDocument;
$dom->load($output);
$files = $sorted = iterator_to_array($dom->getElementsByTagName('file'));
usort($sorted, fn($a, $b) => strcmp($a->getAttribute('name'), $b->getAttribute('name')));
foreach ($files as $file) {
	$file->parentNode->replaceChild(array_shift($sorted)->cloneNode(true), $file);
}
$xml = $dom->saveXML();

Assert::matchFile(__DIR__ . '/CloverXMLGenerator.expected.xml', $xml);
