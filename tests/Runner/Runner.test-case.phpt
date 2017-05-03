<?php

use Tester\Assert;
use Tester\Runner\Runner;
use Tester\Runner\Test;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';


class Logger implements Tester\Runner\OutputHandler
{
	public $results = [];

	function result($testName, $result, $message)
	{
		$this->results[basename($testName)] = [$result, $message == '' ? $message : strtok("$message\n", "\r\n")]; // == to cover NULL and ''
	}

	function begin() {}
	function end() {}
}

$interpreter = createInterpreter();
$interpreter->addPhpIniOption('display_errors', 'on');
$interpreter->addPhpIniOption('html_errors', 'off');

$runner = new Runner($interpreter);
$runner->paths[] = __DIR__ . '/test-case/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->run();

ksort($logger->results);

Assert::same([
	'skip-failed-1.phptx' => [Test::FAILED, "Tester\\TestCaseException: Cannot use @skipOthers simultaneously with @skip on 'testSkip' method."],
	'skip-failed-2.phptx' => [Test::FAILED, "Tester\\TestCaseException: The @skipOthers can be used only once, but found on 'testSkipOne', 'testSkipTwo' methods."],

	'skip-others.phptx [method=testRun]' => [2, "Skipped due to @skipOthers on 'testSkipOthers' method."],
	'skip-others.phptx [method=testSkipOthers]' => [1, NULL],
	'skip-others.phptx [method=testSkip]' => [2, ''],

	'skip.phptx [method=testRun]' => [Test::PASSED, NULL],
	'skip.phptx [method=testSkipMessage]' => [Test::SKIPPED, 'Just skip me.'],
	'skip.phptx [method=testSkip]' => [Test::SKIPPED, ''],
], $logger->results);
