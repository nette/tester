<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';


$runner = new Tester\Runner\Runner(createInterpreter());

$jobs = Assert::with($runner, function () {
	$this->findTests(__DIR__ . '/find-tests/*.phptx');
	$this->findTests(__DIR__ . '/find-tests');
	return $this->jobs;
});

Assert::count(2, $jobs);
