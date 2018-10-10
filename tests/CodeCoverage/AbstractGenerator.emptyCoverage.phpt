<?php

use Tester\CodeCoverage;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/CodeCoverage/Generators/AbstractGenerator.php';


class FakeGenerator extends CodeCoverage\Generators\AbstractGenerator {

	protected function renderSelf() {
		throw new \Exception("Not implemented");
	}
}

$coverageData = Tester\FileMock::create(""); // empty file

\Tester\Assert::exception(function() use ($coverageData) {
	new FakeGenerator($coverageData);
}, "Exception", "There was no coverage data generated. Haven't you forget to call Tester\\Environment::setup() in your tests?");
