<?php

use Tester\Assert;

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

Assert::same(array(
	array('dataProvider.phptx', escapeshellarg('bar')),
	array('dataProvider.phptx', escapeshellarg('foo')),
	array('dataProvider.query.phptx', escapeshellarg('foo 2.2.3')),
	array('dataProvider.query.phptx', escapeshellarg('foo 3 xxx')),
	array('multiple.phptx', '0'),
	array('multiple.phptx', '1'),
	array('testcase.phptx', escapeshellarg('testBar')),
	array('testcase.phptx', escapeshellarg('testFoo')),
), $tests);
