<?php declare(strict_types=1);

use Tester\{Assert, FileMock};
use Tester\Runner\{Output, Test};

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/Output/JUnitPrinter.php';
require __DIR__ . '/../../src/Runner/Output/TapPrinter.php';

$test = (new Test('some.phpt'))->withResult(Test::Failed, "\e[1;31mred\e[0m message");


test('JUnit output contains no ANSI sequences', function () use ($test) {
	$printer = new Output\JUnitPrinter($file = FileMock::create());
	$printer->begin();
	$printer->finish($test);
	$printer->end();

	$dom = new DOMDocument;
	$dom->loadXML(file_get_contents($file));
	Assert::same('red message', $dom->getElementsByTagName('failure')->item(0)->getAttribute('message'));
});


test('TAP output contains no ANSI sequences', function () use ($test) {
	$printer = new Output\TapPrinter($file = FileMock::create());
	$printer->begin();
	$printer->finish($test);
	$printer->end();

	Assert::contains("not ok some.phpt\n# red message\n", file_get_contents($file));
});
