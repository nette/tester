<?php

/**
 * @phpversion 5.4  Requires constant PHP_BINARY available since PHP 5.4.0
 */

use Tester\Assert,
	Tester\Helpers;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/TestHandler.php';
require __DIR__ . '/../Tester/Runner/PhpExecutable.php';
require __DIR__ . '/../Tester/Runner/Runner.php';


$php = new Tester\Runner\PhpExecutable(PHP_BINARY, '-c ' . Tester\Helpers::escapeArg(php_ini_loaded_file()));
$runner = new Tester\Runner\Runner($php);

$tests = Assert::with($runner, function() {
	$this->results = [self::PASSED => 0, self::SKIPPED => 0, self::FAILED => 0];
	$this->findTests(__DIR__ . '/multiple/*.phptx');
	return $this->jobs;
});

foreach ($tests as $i => $job) {
	$tests[$i] = [basename($job->getFile()), $job->getArguments()];
}
sort($tests);

$path = __DIR__ . DIRECTORY_SEPARATOR . 'multiple' . DIRECTORY_SEPARATOR;

Assert::same([
	['dataProvider.phptx', [Helpers::escapeArg('bar'), Helpers::escapeArg("$path../fixtures/dataprovider.ini")]],
	['dataProvider.phptx', [Helpers::escapeArg('foo'), Helpers::escapeArg("$path../fixtures/dataprovider.ini")]],
	['dataProvider.query.phptx', [Helpers::escapeArg('foo 2.2.3'), Helpers::escapeArg("$path../fixtures/dataprovider.query.ini")]],
	['dataProvider.query.phptx', [Helpers::escapeArg('foo 3 xxx'), Helpers::escapeArg("$path../fixtures/dataprovider.query.ini")]],
	['multiple.phptx', ['0']],
	['multiple.phptx', ['1']],
	['testcase.phptx', [Helpers::escapeArg('test1')]],
	['testcase.phptx', [Helpers::escapeArg('testBar')]],
	['testcase.phptx', [Helpers::escapeArg('testFoo')]],
	['testcase.phptx', [Helpers::escapeArg('testPrivate')]],
	['testcase.phptx', [Helpers::escapeArg('testProtected')]],
	['testcase.phptx', [Helpers::escapeArg('test_foo')]],
], $tests);
