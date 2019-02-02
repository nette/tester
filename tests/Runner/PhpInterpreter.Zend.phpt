<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$interpreter = createInterpreter();

Assert::contains(PHP_BINARY, $interpreter->getCommandLine());
Assert::same(PHP_VERSION, $interpreter->getVersion());
Assert::same(extension_loaded('xdebug') || defined('PHPDBG_VERSION') || extension_loaded('pcov'), $interpreter->canMeasureCodeCoverage());
Assert::same(strpos(PHP_SAPI, 'cgi') !== false, $interpreter->isCgi());
