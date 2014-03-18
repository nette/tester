<?php

/**
 * @phpversion 5.4
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/IPhpInterpreter.php';
require __DIR__ . '/../Tester/Runner/ZendPhpBinary.php';


$php = new Tester\Runner\ZendPhpBinary(PHP_BINARY, '-c ' . Tester\Helpers::escapeArg(php_ini_loaded_file()));

Assert::same(PHP_VERSION, $php->getVersion());
Assert::same(extension_loaded('xdebug'), $php->hasXdebug());
Assert::same(strpos(PHP_SAPI, 'cgi') !== FALSE, $php->isCgi());
