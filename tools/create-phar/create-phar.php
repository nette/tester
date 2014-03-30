<?php

// creates tester.phar
if (!class_exists('Phar') || ini_get('phar.readonly')) {
	echo "Enable Phar extension and set directive 'phar.readonly=off'.\n";
	die(1);
}

@unlink('tester.phar'); // @ - file may not exist

$phar = new Phar('tester.phar');
$phar->setStub("<?php
require 'phar://' . __FILE__ . '/tester.php';
__HALT_COMPILER();
");

$phar->startBuffering();
foreach ($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../../Tester', RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
	echo "adding: {$iterator->getSubPathname()}\n";
	$phar[$iterator->getSubPathname()] = php_strip_whitespace($file);
}

$phar->stopBuffering();
$phar->compressFiles(Phar::GZ);

echo "OK\n";
