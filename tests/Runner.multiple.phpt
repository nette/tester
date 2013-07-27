<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/TestHandler.php';
require __DIR__ . '/../Tester/Runner/PhpExecutable.php';
require __DIR__ . '/../Tester/Runner/Runner.php';

if (PHP_VERSION_ID < 50400) {
	Tester\Environment::skip('Requires PHP 5.4.0');
}


$runner = new Tester\Runner\Runner(new Tester\Runner\PhpExecutable(PHP_BINARY));
$fixtures = __DIR__ . DIRECTORY_SEPARATOR . 'multiple' . DIRECTORY_SEPARATOR;

$tests = Assert::with($runner, function() use ($fixtures) {
	$this->results = array(self::PASSED => 0, self::SKIPPED => 0, self::FAILED => 0);
	$this->findTests("$fixtures*.phptx");
	return $this->jobs;
});

foreach ($tests as $i => $job) {
	$tests[$i] = array($job->getFile(), $job->getArguments());
}
sort($tests);

Assert::same(array(
	array($fixtures . 'dataProvider.phptx', escapeshellarg('bar')),
	array($fixtures . 'dataProvider.phptx', escapeshellarg('foo')),
	array($fixtures . 'dataProvider.query.phptx', escapeshellarg('foo 2.2.3')),
	array($fixtures . 'dataProvider.query.phptx', escapeshellarg('foo 3 xxx')),
	array($fixtures . 'multiple.phptx', '0'),
	array($fixtures . 'multiple.phptx', '1'),
	array($fixtures . 'testcase.phptx', escapeshellarg('testBar')),
	array($fixtures . 'testcase.phptx', escapeshellarg('testFoo')),
), $tests);
