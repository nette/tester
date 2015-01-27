<?php

use Tester\Assert,
	Tester\CodeCoverage;

require __DIR__ . '/../bootstrap.php';


if (!extension_loaded('xdebug')) {
	Tester\Environment::skip('Requires Xdebug extension.');
}

CodeCoverage\Collector::start('php://memory');
Assert::exception(function() {
	CodeCoverage\Collector::start('php://memory');
}, 'LogicException', 'Code coverage collector has been already started.');
