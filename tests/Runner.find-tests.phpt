<?php

/**
 * @phpversion 5.4  Requires constant PHP_BINARY available since PHP 5.4.0
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/TestHandler.php';
require __DIR__ . '/../Tester/Runner/Runner.php';


$runner = new Tester\Runner\Runner(createInterpreter());

Assert::with($runner, function() {
	$this->findTests(__DIR__ . '/find-tests/*.phptx');
	Assert::count(1, $this->jobs);
});
