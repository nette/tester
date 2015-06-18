<?php

/**
 * @phpversion 5.4  Requires constant PHP_BINARY available since PHP 5.4.0
 */

use Tester\Assert;
use Tester\Runner\Runner;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';


class Logger implements Tester\Runner\OutputHandler
{
	public $results = [];

	function result($testName, $result, $message)
	{
		$this->results[] = [basename($testName), $result, $message];
	}

	function begin() {}
	function end() {}
}

$runner = new Tester\Runner\Runner(createInterpreter());
$runner->paths[] = __DIR__ . '/annotations/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->run();

sort($logger->results);

$cli = PHP_SAPI === 'cli';
$path = __DIR__ . DIRECTORY_SEPARATOR . 'annotations' . DIRECTORY_SEPARATOR;

Assert::same([
	['dataProvider.error.phptx', Runner::FAILED, "Missing data-provider file '{$path}missing.ini'."],
	['exitCode.die.phptx', Runner::PASSED, NULL],
	['exitCode.error1.phptx', Runner::FAILED, 'Exited with error code 231 (expected 1)'],
	['exitCode.error2.phptx', Runner::FAILED, 'Exited with error code 231 (expected 0)'],
	['exitCode.exception.phptx', Runner::PASSED, NULL],
	['exitCode.exception.pure.phptx', Runner::PASSED, NULL],
	['exitCode.fatalError.phptx', Runner::PASSED, NULL],
	['exitCode.fatalError.pure.phptx', Runner::PASSED, NULL],
	['exitCode.notice.phptx', Runner::PASSED, NULL],
	['exitCode.notice.pure.phptx', Runner::PASSED, NULL],
	['exitCode.notice.shutup.phptx', Runner::PASSED, NULL],
	['httpCode.200.phptx', Runner::PASSED, NULL], // @httpCode is ignored in CLI
	['httpCode.500.phptx', Runner::PASSED, NULL], // @httpCode is ignored in CLI
	['httpCode.error1.phptx', $cli ? Runner::PASSED : Runner::FAILED, $cli ? NULL : 'Exited with HTTP code 200 (expected 500)'], // @httpCode is ignored in CLI
	['httpCode.error2.phptx', $cli ? Runner::PASSED : Runner::FAILED, $cli ? NULL : 'Exited with HTTP code 500 (expected 200)'], // @httpCode is ignored in CLI
	['outputMatch.match.phptx', Runner::PASSED, NULL],
	['outputMatch.notmatch.phptx', Runner::FAILED, "Failed: output should match '%a%Hello%a%'"],
	['outputMatchFile.error.phptx', Runner::FAILED, "Missing matching file '{$path}missing.txt'."],
	['outputMatchFile.match.phptx', Runner::PASSED, NULL],
	['outputMatchFile.notmatch.phptx', Runner::FAILED, "Failed: output should match '%a%Hello%a%'"],
	['phpIni.phptx', Runner::PASSED, NULL],
	['phpversion.match.phptx', Runner::PASSED, NULL],
	['phpversion.notmatch.phptx', Runner::SKIPPED, 'Requires PHP < 5.'],
	['skip.phptx', Runner::SKIPPED, ''],
], $logger->results);
