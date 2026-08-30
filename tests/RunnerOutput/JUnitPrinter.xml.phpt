<?php declare(strict_types=1);

use Tester\Assert;
use Tester\FileMock;
use Tester\Runner\Output\JUnitPrinter;
use Tester\Runner\Test;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/Output/JUnitPrinter.php';


test('report is well-formed XML for any test output', function () {
	$printer = new JUnitPrinter($file = FileMock::create(''));
	$printer->begin();
	$printer->finish(
		(new Test('test.phpt'))
			->withArguments(["a\x01b"])
			->withResult(Test::Failed, "control \x01 char, invalid \xFF UTF-8, <tag> & \"quotes\""),
	);
	$printer->end();

	$dom = new DOMDocument;
	Assert::true(@$dom->loadXML(file_get_contents($file))); // @ - malformed XML triggers warnings
	Assert::same("test.phpt a\u{FFFD}b", $dom->getElementsByTagName('testcase')->item(0)->getAttribute('name'));
	Assert::same(
		"control \u{FFFD} char, invalid \u{FFFD} UTF-8, <tag> & \"quotes\"",
		$dom->getElementsByTagName('failure')->item(0)->getAttribute('message'),
	);
});
