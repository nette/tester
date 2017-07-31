<?php

use Tester\Assert;
use Tester\Runner\Test;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';


class Logger implements Tester\Runner\OutputHandler
{
	public $results = [];


	public function prepare(Test $test)
	{
	}


	public function finish(Test $test)
	{
		$this->results[basename($test->getFile())] = $test->getResult();
	}


	public function begin()
	{
	}


	public function end()
	{
	}
}

Assert::false(getenv('TesterEnvVar'));

$runner = new Tester\Runner\Runner(createInterpreter());
$runner->paths[] = __DIR__ . '/misc/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->setEnvironmentVariable('TesterEnvVar', 'Is here!');
$runner->run();

Assert::false(getenv('TesterEnvVar'));

Assert::same(Test::PASSED, $logger->results['env-vars.phptx']);
