<?php

use Tester\Assert,
	Tester\Helpers;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/TestHandler.php';
require __DIR__ . '/../Tester/Runner/IPhpInterpreter.php';
require __DIR__ . '/../Tester/Runner/ZendPhpBinary.php';
require __DIR__ . '/../Tester/Runner/Runner.php';

if (PHP_VERSION_ID < 50400) {
	Tester\Environment::skip('Requires constant PHP_BINARY available since PHP 5.4.0');
}


$php = new Tester\Runner\ZendPhpBinary(PHP_BINARY, '-c ' . Tester\Helpers::escapeArg(php_ini_loaded_file()));
$runner = new Tester\Runner\Runner($php);

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
	array('dataProvider.phptx', array('bar', "$path../fixtures/dataprovider.ini")),
	array('dataProvider.phptx', array('foo', "$path../fixtures/dataprovider.ini")),
	array('dataProvider.query.phptx', array('foo 2.2.3', "$path../fixtures/dataprovider.query.ini")),
	array('dataProvider.query.phptx', array('foo 3 xxx', "$path../fixtures/dataprovider.query.ini")),
	array('multiple.phptx', array('0')),
	array('multiple.phptx', array('1')),
	array('testcase.phptx', array('test1')),
	array('testcase.phptx', array('testBar')),
	array('testcase.phptx', array('testFoo')),
	array('testcase.phptx', array('testPrivate')),
	array('testcase.phptx', array('testProtected')),
	array('testcase.phptx', array('test_foo')),
), $tests);
