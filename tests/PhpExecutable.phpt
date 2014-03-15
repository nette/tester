<?php

/**
 * @phpversion 5.4
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/PhpExecutable.php';


$php = new Tester\Runner\PhpExecutable(PHP_BINARY, '-c ' . Tester\Helpers::escapeArg(php_ini_loaded_file()));

Assert::contains(PHP_BINARY, $php->getCommandLine());
Assert::same(PHP_VERSION, $php->getVersion());
Assert::same(extension_loaded('xdebug'), $php->hasXdebug());
Assert::same(strpos(PHP_SAPI, 'cgi') !== FALSE, $php->isCgi());
