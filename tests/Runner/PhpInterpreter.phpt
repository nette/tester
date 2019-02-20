<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$interpreter = createInterpreter();

Assert::true($interpreter->hasExtension('DaTe'));
Assert::false($interpreter->hasExtension('foo-bar'));

Assert::contains(PHP_BINARY, $interpreter->getCommandLine());
Assert::same(PHP_VERSION, $interpreter->getVersion());
Assert::same(strpos(PHP_SAPI, 'cgi') !== false, $interpreter->isCgi());

$count = 0;
$engines = $interpreter->getCodeCoverageEngines();
if (defined('PHPDBG_VERSION')) {
	Assert::contains(Tester\CodeCoverage\Collector::ENGINE_PHPDBG, $engines);
	$count++;
}
if (extension_loaded('xdebug')) {
	Assert::contains(Tester\CodeCoverage\Collector::ENGINE_XDEBUG, $engines);
	$count++;
}
Assert::count($count, $engines);
