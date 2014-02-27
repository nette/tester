<?php

/**
 * @phpversion 5.4
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/IExecutable.php';
require __DIR__ . '/../Tester/Runner/HhvmExecutable.php';

if (!defined('HHVM_VERSION')) {
	Tester\Environment::skip('Requires HHVM, not PHP.');
}

$php = new Tester\Runner\HhvmExecutable(PHP_BINARY);

Assert::contains(PHP_BINARY, $php->getCommandLine());
Assert::same(PHP_VERSION, $php->getVersion());
Assert::same(FALSE, $php->isCgi());
