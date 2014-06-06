<?php

use Tester\Assert,
	Tester\FileMock;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/OutputHandler.php';
require __DIR__ . '/../Tester/Runner/Output/FileOutput.php';


class MockOutputHandler implements Tester\Runner\OutputHandler
{
	function begin()
	{
		echo "BEGIN; ";
	}

	function result($testName, $result, $message)
	{
		echo "RESULT($testName, $result, $message); ";
	}

	function end()
	{
		echo "END";
	}
}


$file = FileMock::create('NOT EMPTY');
Assert::same('NOT EMPTY', file_get_contents($file));

$handler = new Tester\Runner\Output\FileOutput(new MockOutputHandler, $file);
$handler->begin();
$handler->result('name', 'result', 'message');
$handler->end();

unset($handler);

Assert::same('BEGIN; RESULT(name, result, message); END', file_get_contents($file));
