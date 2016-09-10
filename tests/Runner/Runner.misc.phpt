<?php

use Tester\Assert;
use Tester\Runner\Runner;
use Tester\Runner\TestInstance;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/TestInstance.php';
require __DIR__ . '/../../src/Runner/Runner.php';


class Logger implements Tester\Runner\OutputHandler
{
	public $results = [];

	function result(TestInstance $testInstance)
	{
		$this->results[basename($testInstance->getTestName())] = $testInstance->getResult();
	}

	function begin() {}
	function end() {}
}

Assert::false(getenv('TesterEnvVar'));

$runner = new Tester\Runner\Runner(createInterpreter());
$runner->paths[] = __DIR__ . '/misc/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->setEnvironmentVariable('TesterEnvVar', 'Is here!');
$runner->run();

Assert::false(getenv('TesterEnvVar'));

Assert::same(Runner::PASSED, $logger->results['env-vars.phptx']);
