<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Expect;
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
		$this->results[basename($test->getFile())] = [$test->getResult(), $test->getDuration()];
	}


	public function begin(): void
	{
	}


	public function end(): void
	{
	}
}

Assert::false(getenv('TesterEnvVar'));

$runner = new Tester\Runner\Runner(createInterpreter());
$runner->paths[] = __DIR__ . '/misc/*.phptx';
$runner->outputHandlers[] = $logger = new Logger;
$runner->setEnvironmentVariable('TesterEnvVar', 'Is here!');
$runner->addPhpIniOption('default_mimetype', 'bar/baz');
$runner->run();

Assert::false(getenv('TesterEnvVar'));

ksort($logger->results);
Assert::equal([
	'addPhpIniOption.phptx' => [Test::PASSED, Expect::type('float')],
	'env-vars.phptx' => [Test::PASSED, Expect::type('float')],
], $logger->results);
