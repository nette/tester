<?php

declare(strict_types=1);

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


	public function prepare(Test $test): void
	{
	}


	public function finish(Test $test): void
	{
		$this->results[] = [basename($test->getFile()), $test->getResult(), $test->message];
	}


	public function begin(): void
	{
	}


	public function end(): void
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
	['dataProvider.error.phptx', Test::Failed, "Missing data provider file '{$path}missing.ini'."],
	['exitCode.die.phptx', Test::Passed, null],
	['exitCode.error1.phptx', Test::Failed, 'Exited with error code 231 (expected 1)'],
	['exitCode.error2.phptx', Test::Failed, 'Exited with error code 231 (expected 0)'],
	['exitCode.exception.phptx', Test::Passed, null],
	['exitCode.exception.pure.phptx', Test::Passed, null],
	['exitCode.fatalError.phptx', Test::Passed, null],
	['exitCode.fatalError.pure.phptx', Test::Passed, null],
	['exitCode.notice.phptx', Test::Passed, null],
	['exitCode.notice.pure.phptx', Test::Passed, null],
	['exitCode.notice.shutup.phptx', Test::Passed, null],
	['httpCode.200.phptx', Test::Passed, null], // @httpCode is ignored in CLI
	['httpCode.500.phptx', Test::Passed, null], // @httpCode is ignored in CLI
	['httpCode.error1.phptx', $cli ? Test::Passed : Test::Failed, $cli ? null : 'Exited with HTTP code 200 (expected 500)'], // @httpCode is ignored in CLI
	['httpCode.error2.phptx', $cli ? Test::Passed : Test::Failed, $cli ? null : 'Exited with HTTP code 500 (expected 200)'], // @httpCode is ignored in CLI
	['outputMatch.match.phptx', Test::Passed, null],
	['outputMatch.notmatch.phptx', Test::Failed, "Failed: output should match '! World !Hello%a%'"],
	['outputMatchFile.error.phptx', Test::Failed, "Missing matching file '{$path}missing.txt'."],
	['outputMatchFile.match.phptx', Test::Passed, null],
	['outputMatchFile.notmatch.phptx', Test::Failed, "Failed: output should match '! World !Hello%a%'"],
	['phpExtension.match.phptx', Test::Passed, null],
	['phpExtension.notmatch.phptx', Test::Skipped, 'Requires PHP extension Foo.'],
	['phpIni.phptx', Test::Passed, null],
	['phpVersion.match.phptx', Test::Passed, null],
	['phpVersion.notmatch.phptx', Test::Skipped, 'Requires PHP < 5.'],
	['skip.phptx', Test::Skipped, ''],
], $logger->results);
