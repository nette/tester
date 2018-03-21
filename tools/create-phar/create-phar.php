<?php

declare(strict_types=1);

// creates tester.phar
if (!class_exists('Phar') || ini_get('phar.readonly')) {
	echo "Enable Phar extension and set directive 'phar.readonly=off'.\n";
	die(1);
}


$build = @exec('git describe --tags 2>&1');
echo "Build: $build\n";

@unlink('tester.phar'); // @ - file may not exist
$phar = new Phar('tester.phar');
$phar->setStub(
"<?php
// Nette Tester $build

if (debug_backtrace()) {
	require 'phar://' . __FILE__ . '/bootstrap.php';
} else {
	require 'phar://' . __FILE__ . '/tester.php';
}
__HALT_COMPILER();
");

$phar->startBuffering();
foreach ($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../../src', RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
	echo "adding: {$iterator->getSubPathname()}\n";
	$phar[$iterator->getSubPathname()] = php_strip_whitespace((string) $file);
}

$phar->stopBuffering();
$phar->compressFiles(Phar::GZ);

echo "OK\n";
