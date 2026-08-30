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


test('summary attributes match the reported test cases', function () {
	$printer = new JUnitPrinter($file = FileMock::create(''));
	$printer->begin();
	$printer->finish((new Test('a.phpt'))->withResult(Test::Passed, null));
	$printer->finish((new Test('b.phpt'))->withResult(Test::Failed, 'failed'));
	$printer->finish((new Test('c.phpt'))->withResult(Test::Failed, 'failed'));
	$printer->finish((new Test('d.phpt'))->withResult(Test::Skipped, 'skipped'));
	$printer->end();

	$dom = new DOMDocument;
	$dom->loadXML(file_get_contents($file));
	$suite = $dom->getElementsByTagName('testsuite')->item(0);
	Assert::same('4', $suite->getAttribute('tests'));
	Assert::same('2', $suite->getAttribute('failures'));
	Assert::same((string) $dom->getElementsByTagName('failure')->length, $suite->getAttribute('failures'));
	Assert::same((string) $dom->getElementsByTagName('skipped')->length, $suite->getAttribute('skipped'));
	Assert::same('0', $suite->getAttribute('errors'));
});
