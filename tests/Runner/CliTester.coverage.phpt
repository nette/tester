<?php declare(strict_types=1);

use Tester\Assert;
use Tester\FileMock;
use Tester\Runner\CliTester;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/CliTester.php';
require __DIR__ . '/../../src/CodeCoverage/PhpParser.php';
require __DIR__ . '/../../src/CodeCoverage/Generators/AbstractGenerator.php';
require __DIR__ . '/../../src/CodeCoverage/Generators/CloverXMLGenerator.php';


$source = realpath(__DIR__ . '/../../src/Runner/Test.php');

$finishCoverage = fn(?string $stdoutFormat, string $data): array => Assert::with(new CliTester, function () use ($stdoutFormat, $data, $source): array {
	$this->stdoutFormat = $stdoutFormat;
	$this->options = ['--coverage-src' => [$source]];
	ob_start();
	$result = $this->finishCodeCoverage(FileMock::create($data, 'xml'));
	return [$result, ob_get_clean()];
});


test('console output', function () use ($finishCoverage, $source) {
	[$result, $output] = $finishCoverage(null, serialize([$source => [1 => 1, 2 => -1]]));
	Assert::true($result);
	Assert::match('Generating code coverage report... %d%%% covered', $output);
});


test('machine-readable stdout contains nothing else', function () use ($finishCoverage, $source) {
	foreach (['junit', 'tap', 'none'] as $format) {
		Assert::same([true, ''], $finishCoverage($format, serialize([$source => [1 => 1]])), $format);
	}
});


test('empty coverage file is an error', function () use ($finishCoverage) {
	Assert::same(
		[false, "Generating code coverage report... failed. Coverage file is empty. Do you call Tester\\Environment::setup() in tests?\n"],
		$finishCoverage(null, ''),
	);
	Assert::same([false, ''], $finishCoverage('junit', ''));
});
