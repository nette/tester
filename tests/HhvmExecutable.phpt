<?php

/**
 * @phpversion 5.4
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/IPhpInterpreter.php';
require __DIR__ . '/../Tester/Runner/HhvmExecutable.php';

if (!defined('HHVM_VERSION')) {
	Tester\Environment::skip('Not supported under PHP.');
}

$php = createExecutable(PHP_BINARY);

Assert::contains(PHP_BINARY, $php->getCommandLine());
Assert::same(PHP_VERSION, $php->getVersion());
Assert::same(FALSE, $php->isCgi());
