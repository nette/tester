<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Runner\Test;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';


$runner = new Tester\Runner\Runner(createInterpreter());

$jobs = Assert::with($runner, function () {
	$this->result = true;
	$this->findTests(__DIR__ . '/multiple/*.phptx');
	return $this->jobs;
});


/** @var Tester\Runner\Job[] $jobs */
foreach ($jobs as $i => $job) {
	$jobs[$i] = [basename($job->getTest()->getFile()), $job->getTest()->getArguments()];
}
sort($jobs);

$path = __DIR__ . DIRECTORY_SEPARATOR . 'multiple' . DIRECTORY_SEPARATOR;

Assert::same([
	['dataProvider.multiple.phptx', [['dataprovider', "0|$path../../Framework/fixtures/dataprovider.ini"], ['multiple', '0']]],
	['dataProvider.multiple.phptx', [['dataprovider', "0|$path../../Framework/fixtures/dataprovider.ini"], ['multiple', '1']]],
	['dataProvider.multiple.phptx', [['dataprovider', "1|$path../../Framework/fixtures/dataprovider.ini"], ['multiple', '0']]],
	['dataProvider.multiple.phptx', [['dataprovider', "1|$path../../Framework/fixtures/dataprovider.ini"], ['multiple', '1']]],
	['dataProvider.multiple.phptx', [['dataprovider', "bar 1|$path../../Framework/fixtures/dataprovider.ini"], ['multiple', '0']]],
	['dataProvider.multiple.phptx', [['dataprovider', "bar 1|$path../../Framework/fixtures/dataprovider.ini"], ['multiple', '1']]],
	['dataProvider.multiple.phptx', [['dataprovider', "bar 2|$path../../Framework/fixtures/dataprovider.ini"], ['multiple', '0']]],
	['dataProvider.multiple.phptx', [['dataprovider', "bar 2|$path../../Framework/fixtures/dataprovider.ini"], ['multiple', '1']]],
	['dataProvider.multiple.phptx', [['dataprovider', "foo|$path../../Framework/fixtures/dataprovider.ini"], ['multiple', '0']]],
	['dataProvider.multiple.phptx', [['dataprovider', "foo|$path../../Framework/fixtures/dataprovider.ini"], ['multiple', '1']]],

	['dataProvider.phptx', [['dataprovider', "0|$path../../Framework/fixtures/dataprovider.ini"]]],
	['dataProvider.phptx', [['dataprovider', "1|$path../../Framework/fixtures/dataprovider.ini"]]],
	['dataProvider.phptx', [['dataprovider', "bar 1|$path../../Framework/fixtures/dataprovider.ini"]]],
	['dataProvider.phptx', [['dataprovider', "bar 2|$path../../Framework/fixtures/dataprovider.ini"]]],
	['dataProvider.phptx', [['dataprovider', "foo|$path../../Framework/fixtures/dataprovider.ini"]]],

	['dataProvider.query.phptx', [['dataprovider', "foo 2.2.3|$path../../Framework/fixtures/dataprovider.query.ini"]]],
	['dataProvider.query.phptx', [['dataprovider', "foo 3 xxx|$path../../Framework/fixtures/dataprovider.query.ini"]]],

	['multiple.phptx', [['multiple', '0']]],
	['multiple.phptx', [['multiple', '1']]],

	// TestCase methods are not known yet, the queue contains a discovery job
	['testcase.phptx', [['method', 'nette-tester-list-methods']]],
], $jobs);


// TestCase methods are discovered in parallel during run and expanded to individual jobs
class Collector implements Tester\Runner\OutputHandler
{
	public $tests = [];


	public function begin(): void
	{
	}


	public function prepare(Test $test): void
	{
	}


	public function finish(Test $test): void
	{
		$this->tests[] = [basename($test->getFile()), $test->getArguments()];
	}


	public function end(): void
	{
	}
}

$runner = new Tester\Runner\Runner(createInterpreter());
$runner->paths[] = __DIR__ . '/multiple/testcase.phptx';
$runner->outputHandlers[] = $collector = new Collector;
$runner->run();

sort($collector->tests);

Assert::same([
	['testcase.phptx', [['method', 'test1']]],
	['testcase.phptx', [['method', 'testBar']]],
	['testcase.phptx', [['method', 'testFoo']]],
	['testcase.phptx', [['method', 'testPrivate']]],
	['testcase.phptx', [['method', 'testProtected']]],
	['testcase.phptx', [['method', 'test_foo']]],
], $collector->tests);


// invalid @multiple value
$runner = new Tester\Runner\Runner(createInterpreter());
$runner->outputHandlers[] = $logger = new class implements Tester\Runner\OutputHandler {
	public $results = [];


	public function begin(): void
	{
	}


	public function prepare(Test $test): void
	{
	}


	public function finish(Test $test): void
	{
		$this->results[basename($test->getFile())] = [$test->getResult(), $test->message];
	}


	public function end(): void
	{
	}
};

$jobs = Assert::with($runner, function () {
	$this->result = true;
	$this->jobs = [];
	$this->findTests(__DIR__ . '/multiple-invalid/*.phptx');
	return $this->jobs;
});

Assert::same([], $jobs);
ksort($logger->results);
Assert::same([
	'text.phptx' => [Test::Failed, "Annotation @multiple expects a positive integer, 'abc' given."],
	'zero.phptx' => [Test::Failed, "Annotation @multiple expects a positive integer, '0' given."],
], $logger->results);
