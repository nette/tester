<?php declare(strict_types=1);

use Tester\{Ansi, Assert, FileMock};
use Tester\Runner\{Output, Test};

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/Output/ConsolePrinter.php';

$runner = new Tester\Runner\Runner(createInterpreter());
$printer = new Output\ConsolePrinter($runner, false, $file = FileMock::create(), slowestCount: 2);

$printer->begin();
foreach ([['a.phpt', 0.1], ['b.phpt', 0.5], ['c.phpt', 0.3]] as [$name, $duration]) {
	$test = new Test(__DIR__ . DIRECTORY_SEPARATOR . $name);
	$printer->prepare($test);
	$printer->finish($test->withResult(Test::Passed, null, $duration));
}

$printer->end();

Assert::match(
	<<<'XX'
		%A%
		Slowest tests:
		· b.phpt in 0.500 s
		· c.phpt in 0.300 s

		XX,
	Ansi::stripAnsi(file_get_contents($file)),
);
