<?php

use Tester\Assert;
use Tester\Runner\Test;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';


class Logger implements Tester\Runner\OutputHandler
{
	public $results = [];


	public function result($testName, $result, $message)
	{
		$this->results[] = [basename($testName), $result, $message];
	}


	public function begin()
	{
	}


	public function end()
	{
	}
}

$runner = new Tester\Runner\Runner(createInterpreter());
$runner->paths[] = __DIR__ . '/annotations/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->run();

sort($logger->results);

$cli = PHP_SAPI === 'cli';
$path = __DIR__ . DIRECTORY_SEPARATOR . 'annotations' . DIRECTORY_SEPARATOR;

Assert::same([
	['dataProvider.error.phptx', Test::FAILED, "Missing data-provider file '{$path}missing.ini'."],
	['exitCode.die.phptx', Test::PASSED, NULL],
	['exitCode.error1.phptx', Test::FAILED, 'Exited with error code 231 (expected 1)'],
	['exitCode.error2.phptx', Test::FAILED, 'Exited with error code 231 (expected 0)'],
	['exitCode.exception.phptx', Test::PASSED, NULL],
	['exitCode.exception.pure.phptx', Test::PASSED, NULL],
	['exitCode.fatalError.phptx', Test::PASSED, NULL],
	['exitCode.fatalError.pure.phptx', Test::PASSED, NULL],
	['exitCode.notice.phptx', Test::PASSED, NULL],
	['exitCode.notice.pure.phptx', Test::PASSED, NULL],
	['exitCode.notice.shutup.phptx', Test::PASSED, NULL],
	['httpCode.200.phptx', Test::PASSED, NULL], // @httpCode is ignored in CLI
	['httpCode.500.phptx', Test::PASSED, NULL], // @httpCode is ignored in CLI
	['httpCode.error1.phptx', $cli ? Test::PASSED : Test::FAILED, $cli ? NULL : 'Exited with HTTP code 200 (expected 500)'], // @httpCode is ignored in CLI
	['httpCode.error2.phptx', $cli ? Test::PASSED : Test::FAILED, $cli ? NULL : 'Exited with HTTP code 500 (expected 200)'], // @httpCode is ignored in CLI
	['outputMatch.match.phptx', Test::PASSED, NULL],
	['outputMatch.notmatch.phptx', Test::FAILED, "Failed: output should match '! World !Hello%a%'"],
	['outputMatchFile.error.phptx', Test::FAILED, "Missing matching file '{$path}missing.txt'."],
	['outputMatchFile.match.phptx', Test::PASSED, NULL],
	['outputMatchFile.notmatch.phptx', Test::FAILED, "Failed: output should match '! World !Hello%a%'"],
	['phpIni.phptx', Test::PASSED, NULL],
	['phpversion.match.phptx', Test::PASSED, NULL],
	['phpversion.notmatch.phptx', Test::SKIPPED, 'Requires PHP < 5.'],
	['skip.phptx', Test::SKIPPED, ''],
], $logger->results);
