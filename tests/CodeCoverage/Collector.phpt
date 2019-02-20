<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\CodeCoverage;
use Tester\FileMock;

require __DIR__ . '/../bootstrap.php';


$engines = CodeCoverage\Collector::detectEngines();
if (count($engines) < 1) {
	Tester\Environment::skip('Requires Xdebug or phpdbg SAPI.');
}
$engine = reset($engines);

if (CodeCoverage\Collector::isStarted()) {
	Tester\Environment::skip('Requires running without --coverage.');
}

$outputFile = FileMock::create('');

Assert::false(CodeCoverage\Collector::isStarted());
CodeCoverage\Collector::start($outputFile, $engine);
Assert::true(CodeCoverage\Collector::isStarted());

Assert::exception(function () use ($outputFile, $engine) {
	CodeCoverage\Collector::start($outputFile, $engine);
}, LogicException::class, 'Code coverage collector has been already started.');

$content = file_get_contents($outputFile);
Assert::same('', $content);

CodeCoverage\Collector::save();
$coverage = unserialize(file_get_contents($outputFile));
Assert::type('array', $coverage);
Assert::same(1, $coverage[__FILE__][__LINE__ - 3]); // line with 1st Collector::save()

CodeCoverage\Collector::save(); // save() can be called repeatedly
$coverage = unserialize(file_get_contents($outputFile));
Assert::type('array', $coverage);
Assert::same(1, $coverage[__FILE__][__LINE__ - 8]); // line with 1st Collector::save()
Assert::same(1, $coverage[__FILE__][__LINE__ - 4]); // line with 2nd Collector::save()
