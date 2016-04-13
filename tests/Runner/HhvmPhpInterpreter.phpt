<?php

use Tester\Assert;
use Tester\Environment;

require __DIR__ . '/../bootstrap.php';

if (!defined('HHVM_VERSION')) {
	Environment::skip('Test requires HHVM binary.');
}

$executable = createInterpreter();

Assert::contains(PHP_BINARY, $executable->getCommandLine());
Assert::same(PHP_VERSION, $executable->getVersion());
Assert::same(FALSE, $executable->canMeasureCodeCoverage());
Assert::same(FALSE, $executable->isCgi());
