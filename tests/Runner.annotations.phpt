<?php

use Tester\Assert,
	Tester\Runner\Runner;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/OutputHandler.php';
require __DIR__ . '/../Tester/Runner/TestHandler.php';
require __DIR__ . '/../Tester/Runner/PhpExecutable.php';
require __DIR__ . '/../Tester/Runner/Runner.php';

if (PHP_VERSION_ID < 50400) {
	Tester\Environment::skip('Requires constant PHP_BINARY available since PHP 5.4.0');
}


class Logger implements Tester\Runner\OutputHandler
{
	public $results = array();

	function result($testName, $result, $message, $runTime)
	{
		$this->results[] = array(basename($testName), $result, $message, NULL);
	}

	function begin() {}
	function end() {}
}

$runner = new Tester\Runner\Runner(new Tester\Runner\PhpExecutable(PHP_BINARY));
$runner->paths[] = __DIR__ . '/annotations/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->run();

sort($logger->results);

$cli = PHP_SAPI === 'cli';
$path = __DIR__ . DIRECTORY_SEPARATOR . 'annotations' . DIRECTORY_SEPARATOR;

Assert::same(array(
	array('dataProvider.error.phptx', Runner::FAILED, "Missing data-provider file '{$path}missing.ini'.", NULL),
	array('exitCode.die.phptx', Runner::PASSED, NULL, NULL),
	array('exitCode.error1.phptx', Runner::FAILED, "Exited with error code 231 (expected 1)", NULL),
	array('exitCode.error2.phptx', Runner::FAILED, "Exited with error code 231 (expected 0)", NULL),
	array('exitCode.exception.phptx', Runner::PASSED, NULL, NULL),
	array('exitCode.exception.pure.phptx', Runner::PASSED, NULL, NULL),
	array('exitCode.fatalError.phptx', Runner::PASSED, NULL, NULL),
	array('exitCode.fatalError.pure.phptx', Runner::PASSED, NULL, NULL),
	array('exitCode.notice.phptx', Runner::PASSED, NULL, NULL),
	array('exitCode.notice.pure.phptx', Runner::PASSED, NULL, NULL),
	array('exitCode.notice.shutup.phptx', Runner::PASSED, NULL, NULL),
	array('httpCode.200.phptx', Runner::PASSED, NULL, NULL), // @httpCode is ignored in CLI
	array('httpCode.500.phptx', Runner::PASSED, NULL, NULL), // @httpCode is ignored in CLI
	array('httpCode.error1.phptx', $cli ? Runner::PASSED : Runner::FAILED, $cli ? NULL : 'Exited with HTTP code 200 (expected 500)', NULL), // @httpCode is ignored in CLI
	array('httpCode.error2.phptx', $cli ? Runner::PASSED : Runner::FAILED, $cli ? NULL : 'Exited with HTTP code 500 (expected 200)', NULL), // @httpCode is ignored in CLI
	array('outputMatch.match.phptx', Runner::PASSED, NULL, NULL),
	array('outputMatch.notmatch.phptx', Runner::FAILED, "Failed: output should match '%a%Hello%a%'", NULL),
	array('outputMatchFile.error.phptx', Runner::FAILED, "Missing matching file '{$path}missing.txt'.", NULL),
	array('outputMatchFile.match.phptx', Runner::PASSED, NULL, NULL),
	array('outputMatchFile.notmatch.phptx', Runner::FAILED, "Failed: output should match '%a%Hello%a%'", NULL),
	array('phpIni.phptx', Runner::PASSED, NULL, NULL),
	array('phpversion.match.phptx', Runner::PASSED, NULL, NULL),
	array('phpversion.notmatch.phptx', Runner::SKIPPED, 'Requires PHP < 5.', NULL),
	array('skip.phptx', Runner::SKIPPED, '', NULL),
), $logger->results);
