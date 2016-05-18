<?php

use Tester\Assert;
use Tester\Environment;
use Tester\Runner\Runner;
use Tester\Runner\Output\JUnitPrinter;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/Output/JUnitPrinter.php';


$runner = new Runner(createInterpreter());
$runner->setEnvironmentVariable(Environment::COLORS, 0);
$runner->outputHandlers[] = new JUnitPrinter($runner);
$runner->paths[] = __DIR__ . '/cases/*.phptx';
ob_start();
$runner->run();
$output = ob_get_clean();

$expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
	<testsuite errors="1" skipped="1" tests="3" time="%a%" timestamp="%a%">
		<testcase classname="RunnerOutput%ds%cases%ds%fail.phptx" name="RunnerOutput%ds%cases%ds%fail.phptx">
			<failure message="Failed: STOP

in RunnerOutput%ds%cases%ds%fail.phptx(4) Tester\\Assert::fail('STOP');"/>
		</testcase>
		<testcase classname="RunnerOutput%ds%cases%ds%pass.phptx" name="RunnerOutput%ds%cases%ds%pass.phptx"/>
		<testcase classname="RunnerOutput%ds%cases%ds%skip.phptx" name="RunnerOutput%ds%cases%ds%skip.phptx">
			<skipped/>
		</testcase>
	</testsuite>
</testsuites>
XML;

Assert::match($expected, $output);
