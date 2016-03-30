<?php

use Tester\Assert;
use Tester\CodeCoverage;
use Tester\FileMock;

require __DIR__ . '/../bootstrap.php';


if (!extension_loaded('xdebug') && (!defined('PHPDBG_VERSION') || PHP_VERSION_ID < 70000)) {
	Tester\Environment::skip('Requires Xdebug or phpdbg SAPI.');
}

if (CodeCoverage\Collector::isStarted()) {
	Tester\Environment::skip('Requires running without --coverage.');
}

$outputFile = FileMock::create('');

Assert::false(CodeCoverage\Collector::isStarted());
CodeCoverage\Collector::start($outputFile);
Assert::true(CodeCoverage\Collector::isStarted());

Assert::exception(function () use ($outputFile) {
	CodeCoverage\Collector::start($outputFile);
}, 'LogicException', 'Code coverage collector has been already started.');

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
