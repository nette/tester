<?php

use Tester\Assert,
	Tester\Helpers;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/TestHandler.php';
require __DIR__ . '/../Tester/Runner/PhpExecutable.php';
require __DIR__ . '/../Tester/Runner/Runner.php';

if (PHP_VERSION_ID < 50400) {
	Tester\Environment::skip('Requires constant PHP_BINARY available since PHP 5.4.0');
}


$runner = new Tester\Runner\Runner(new Tester\Runner\PhpExecutable(PHP_BINARY));

$tests = Assert::with($runner, function() {
	$this->results = array(self::PASSED => 0, self::SKIPPED => 0, self::FAILED => 0);
	$this->findTests(__DIR__ . '/multiple/*.phptx');
	return $this->jobs;
});

foreach ($tests as $i => $job) {
	$tests[$i] = array(basename($job->getFile()), $job->getArguments());
}
sort($tests);

$path = __DIR__ . DIRECTORY_SEPARATOR . 'multiple' . DIRECTORY_SEPARATOR;

Assert::same(array(
	array('dataProvider.phptx', Helpers::escapeArg('bar') . ' ' . Helpers::escapeArg("$path../fixtures/dataprovider.ini")),
	array('dataProvider.phptx', Helpers::escapeArg('foo') . ' ' . Helpers::escapeArg("$path../fixtures/dataprovider.ini")),
	array('dataProvider.query.phptx', Helpers::escapeArg('foo 2.2.3') . ' ' . Helpers::escapeArg("$path../fixtures/dataprovider.query.ini")),
	array('dataProvider.query.phptx', Helpers::escapeArg('foo 3 xxx') . ' ' . Helpers::escapeArg("$path../fixtures/dataprovider.query.ini")),
	array('multiple.phptx', '0'),
	array('multiple.phptx', '1'),
	array('testcase.phptx', Helpers::escapeArg('test1')),
	array('testcase.phptx', Helpers::escapeArg('testBar')),
	array('testcase.phptx', Helpers::escapeArg('testFoo')),
	array('testcase.phptx', Helpers::escapeArg('testPrivate')),
	array('testcase.phptx', Helpers::escapeArg('testProtected')),
	array('testcase.phptx', Helpers::escapeArg('test_foo')),
), $tests);
