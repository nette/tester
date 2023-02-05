<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
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

	['testcase.phptx', [['method', 'test1']]],
	['testcase.phptx', [['method', 'testBar']]],
	['testcase.phptx', [['method', 'testFoo']]],
	['testcase.phptx', [['method', 'testPrivate']]],
	['testcase.phptx', [['method', 'testProtected']]],
	['testcase.phptx', [['method', 'test_foo']]],
], $jobs);
