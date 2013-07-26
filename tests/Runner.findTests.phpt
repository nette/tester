<?php

/**
 * @phpversion 5.4
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/TestHandler.php';
require __DIR__ . '/../Tester/Runner/PhpExecutable.php';
require __DIR__ . '/../Tester/Runner/Runner.php';


$php = new Tester\Runner\PhpExecutable(PHP_BINARY);
$runner = new Tester\Runner\Runner($php);
$runner->paths[] = $fixtures = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR;

$tests = Assert::with($runner, function() {
	$this->results = array($this::SKIPPED => 0);
	$this->findTests();
	return $this->jobs;
});

foreach ($tests as $i => $job) {
	$tests[$i] = array($job->getFile(), $job->getArguments());
}
sort($tests);

Assert::same(array(
	array($fixtures . 'test.dataProvider.phpt', escapeshellarg('bar')),
	array($fixtures . 'test.dataProvider.phpt', escapeshellarg('foo')),
	array($fixtures . 'test.dataProvider.query.phpt', escapeshellarg('foo 2.2.3')),
	array($fixtures . 'test.dataProvider.query.phpt', escapeshellarg('foo 3 xxx')),
	array($fixtures . 'test.multiple.phpt', escapeshellarg('0')),
	array($fixtures . 'test.multiple.phpt', escapeshellarg('1')),
	array($fixtures . 'test.phpt', NULL),
	array($fixtures . 'testcase.phpt', escapeshellarg('testBar')),
	array($fixtures . 'testcase.phpt', escapeshellarg('testFoo')),
), $tests);
