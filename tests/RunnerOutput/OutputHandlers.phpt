<?php

/**
 * TEST: Output handlers.
 */

use Tester\Assert;
use Tester\Dumper;
use Tester\FileMock;
use Tester\Runner\Output;
use Tester\Runner\Runner;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/Output/ConsolePrinter.php';
require __DIR__ . '/../../src/Runner/Output/JUnitPrinter.php';
require __DIR__ . '/../../src/Runner/Output/Logger.php';
require __DIR__ . '/../../src/Runner/Output/TapPrinter.php';


$runner = new Runner(createInterpreter());
$runner->paths[] = __DIR__ . '/cases/*.phptx';
$runner->outputHandlers[] = new Output\ConsolePrinter($runner, false, $console = FileMock::create(''));
$runner->outputHandlers[] = new Output\ConsolePrinter($runner, true, $consoleWithSkipped = FileMock::create(''));
$runner->outputHandlers[] = new Output\JUnitPrinter($runner, $jUnit = FileMock::create(''));
$runner->outputHandlers[] = new Output\Logger($runner, $logger = FileMock::create(''));
$runner->outputHandlers[] = new Output\TapPrinter($runner, $tap = FileMock::create(''));
$runner->run();


Assert::matchFile(__DIR__ . '/OutputHandlers.expect.console.txt', Dumper::removeColors(file_get_contents($console)));
Assert::matchFile(__DIR__ . '/OutputHandlers.expect.consoleWithSkip.txt', Dumper::removeColors(file_get_contents($consoleWithSkipped)));
Assert::matchFile(__DIR__ . '/OutputHandlers.expect.jUnit.xml', Dumper::removeColors(file_get_contents($jUnit)));
Assert::matchFile(__DIR__ . '/OutputHandlers.expect.logger.txt', Dumper::removeColors(file_get_contents($logger)));
Assert::matchFile(__DIR__ . '/OutputHandlers.expect.tap.txt', Dumper::removeColors(file_get_contents($tap)));
