<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Dumper;
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
		$this->results[basename($test->getFile())] = [$test->getResult(), $test->message];
	}


	public function begin(): void
	{
	}


	public function end(): void
	{
	}
}


Tester\Helpers::purge(__DIR__ . '/snapshots/snapshots');


// first run, without update -> fail

$runner = new Tester\Runner\Runner(createInterpreter());
$runner->paths[] = __DIR__ . '/snapshots/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->run();

Assert::same(Test::FAILED, $logger->results['update-snapshots.phptx'][0]);
Assert::match(
	"Failed: Missing snapshot '%a%', use --update-snapshots option to generate it.\n%A%",
	trim(Dumper::removeColors($logger->results['update-snapshots.phptx'][1]))
);

// second run, with update -> fail

$runner = new Tester\Runner\Runner(createInterpreter());
$runner->paths[] = __DIR__ . '/snapshots/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->setEnvironmentVariable(Tester\Environment::UPDATE_SNAPSHOTS, '1');
$runner->run();

Assert::same(Test::FAILED, $logger->results['update-snapshots.phptx'][0]);
Assert::match(
	"The following snapshots were updated, please make sure they are correct:\n%a%.snapshot.phps",
	trim(Dumper::removeColors($logger->results['update-snapshots.phptx'][1]))
);

// third run, without update -> pass

$runner = new Tester\Runner\Runner(createInterpreter());
$runner->paths[] = __DIR__ . '/snapshots/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->run();

Assert::same(Test::PASSED, $logger->results['update-snapshots.phptx'][0]);
