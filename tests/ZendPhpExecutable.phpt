<?php

/**
 * @phpversion 5.4
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/PhpInterpreter.php';
require __DIR__ . '/../Tester/Runner/ZendPhpInterpreter.php';


$php = new Tester\Runner\ZendPhpInterpreter(PHP_BINARY, '-c ' . Tester\Helpers::escapeArg(php_ini_loaded_file()));

Assert::contains(PHP_BINARY, $php->getCommandLine());
Assert::same(PHP_VERSION, $php->getVersion());
Assert::same(extension_loaded('xdebug'), $php->hasXdebug());
Assert::same(strpos(PHP_SAPI, 'cgi') !== FALSE, $php->isCgi());
