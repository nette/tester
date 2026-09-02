<?php declare(strict_types=1);

use Tester\Assert;
use Tester\CodeCoverage\Generators\HtmlGenerator;
use Tester\FileMock;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/CodeCoverage/Generators/AbstractGenerator.php';
require __DIR__ . '/../../src/CodeCoverage/Generators/HtmlGenerator.php';


test('file containing only dead code', function () {
	$source = realpath(__DIR__ . '/fixtures.clover/Logger.php');
	$generator = new HtmlGenerator(FileMock::create(serialize([$source => [1 => -2, 2 => -2]])), [$source]);
	$generator->render($output = FileMock::create('', 'html'));

	Assert::contains('"coverage":100', file_get_contents($output));
});
