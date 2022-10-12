<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$interpreter = createInterpreter();

Assert::true($interpreter->hasExtension('DaTe'));
Assert::false($interpreter->hasExtension('foo-bar'));

Assert::contains(PHP_BINARY, $interpreter->getCommandLine());
Assert::same(PHP_VERSION, $interpreter->getVersion());
Assert::same(str_contains(PHP_SAPI, 'cgi'), $interpreter->isCgi());

$count = 0;
$engines = $interpreter->getCodeCoverageEngines();
if (defined('PHPDBG_VERSION')) {
	Assert::contains([Tester\CodeCoverage\Collector::ENGINE_PHPDBG, PHPDBG_VERSION], $engines);
	$count++;
}

if (extension_loaded('xdebug')) {
	Assert::contains([Tester\CodeCoverage\Collector::ENGINE_XDEBUG, phpversion('xdebug')], $engines);
	$count++;
}

if (extension_loaded('pcov')) {
	Assert::contains([Tester\CodeCoverage\Collector::ENGINE_PCOV, phpversion('pcov')], $engines);
	$count++;
}

Assert::count($count, $engines);
