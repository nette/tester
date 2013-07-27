<?php

use Tester\Assert,
	Tester\Runner\Runner;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/OutputHandler.php';
require __DIR__ . '/../Tester/Runner/TestHandler.php';
require __DIR__ . '/../Tester/Runner/PhpExecutable.php';
require __DIR__ . '/../Tester/Runner/Runner.php';

if (PHP_VERSION_ID < 50400) {
	Tester\Environment::skip('Requires PHP 5.4.0');
}


class Logger implements Tester\Runner\OutputHandler
{
	public $results = array();

	function result($testName, $result, $message)
	{
		$this->results[] = array($testName, $result, $message);
	}

	function begin() {}
	function end() {}
}

$runner = new Tester\Runner\Runner(new Tester\Runner\PhpExecutable(PHP_BINARY));
$runner->paths[] = __DIR__ . '/annotations/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->run();

usort($logger->results, function($a, $b) { return strcmp($a[0], $b[0]); });

$cli = PHP_SAPI === 'cli';
$path = __DIR__ . DIRECTORY_SEPARATOR . 'annotations' . DIRECTORY_SEPARATOR;
$prefix = 'tests' . DIRECTORY_SEPARATOR . 'annotations' . DIRECTORY_SEPARATOR;
Assert::same(array(
	array($prefix . 'dataProvider.error.phptx', Runner::FAILED, "Missing data-provider file '{$path}missing.ini'."),
	array($prefix . 'exitCode.die.phptx', Runner::PASSED, NULL),
	array($prefix . 'exitCode.dieAnotherDay.phptx', !$cli || PHP_VERSION_ID > 50406 ? Runner::PASSED : Runner::FAILED, !$cli || PHP_VERSION_ID > 50406 ? NULL : 'Exited with error code 0 (expected 231)'), // PHP bug #62725
	array($prefix . 'exitCode.error1.phptx', Runner::FAILED, "Exited with error code 231 (expected 1)"),
	array($prefix . 'exitCode.error2.phptx', Runner::FAILED, "Exited with error code 231 (expected 0)"),
	array($prefix . 'exitCode.exception.phptx', Runner::PASSED, NULL),
	array($prefix . 'exitCode.exception.pure.phptx', Runner::PASSED, NULL),
	array($prefix . 'exitCode.fatalError.phptx', Runner::PASSED, NULL),
	array($prefix . 'exitCode.fatalError.pure.phptx', Runner::PASSED, NULL),
	array($prefix . 'exitCode.notice.phptx', Runner::PASSED, NULL),
	array($prefix . 'exitCode.notice.pure.phptx', Runner::PASSED, NULL),
	array($prefix . 'exitCode.notice.shutup.phptx', Runner::PASSED, NULL),
	array($prefix . 'httpCode.200.phptx', Runner::PASSED, NULL),
	array($prefix . 'httpCode.500.phptx', Runner::PASSED, NULL),
	array($prefix . 'httpCode.error1.phptx', $cli ? Runner::PASSED : Runner::FAILED, $cli ? NULL : 'Exited with HTTP code 200 (expected 500)'),
	array($prefix . 'httpCode.error2.phptx', $cli ? Runner::PASSED : Runner::FAILED, $cli ? NULL : 'Exited with HTTP code 500 (expected 200)'),
	array($prefix . 'outputMatch.match.phptx', Runner::PASSED, NULL),
	array($prefix . 'outputMatch.notmatch.phptx', Runner::FAILED, "Failed: output should match '%a%Hello%a%'"),
	array($prefix . 'outputMatchFile.error.phptx', Runner::FAILED, "Missing matching file '{$path}missing.txt'."),
	array($prefix . 'outputMatchFile.match.phptx', Runner::PASSED, NULL),
	array($prefix . 'outputMatchFile.notmatch.phptx', Runner::FAILED, "Failed: output should match '%a%Hello%a%'"),
	array($prefix . 'phpIni.phptx', Runner::PASSED, NULL),
	array($prefix . 'phpversion.match.phptx', Runner::PASSED, NULL),
	array($prefix . 'phpversion.notmatch.phptx', Runner::SKIPPED, 'Requires PHP < 5.'),
	array($prefix . 'skip.phptx', Runner::SKIPPED, ''),
), $logger->results);
