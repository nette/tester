<?php

/**
 * @phpversion 5.4
 */

use Tester\Assert;
use Tester\Environment;

require __DIR__ . '/../bootstrap.php';

if (defined('HHVM_VERSION')) {
	Environment::skip('Test requires PHP binary.');
}

$interpreter = createInterpreter();

Assert::contains(PHP_BINARY, $interpreter->getCommandLine());
Assert::same(PHP_VERSION, $interpreter->getVersion());
Assert::same(extension_loaded('xdebug'), $interpreter->hasXdebug());
Assert::same(strpos(PHP_SAPI, 'cgi') !== FALSE, $interpreter->isCgi());
