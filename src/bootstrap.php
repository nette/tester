<?php

/**
 * Test environment initialization.
 */

declare(strict_types=1);

require __DIR__ . '/Framework/Helpers.php';
require __DIR__ . '/Framework/Environment.php';
require __DIR__ . '/Framework/DataProvider.php';
require __DIR__ . '/Framework/Assert.php';
require __DIR__ . '/Framework/AssertException.php';
require __DIR__ . '/Framework/Dumper.php';
require __DIR__ . '/Framework/FileMock.php';
require __DIR__ . '/Framework/TestCase.php';
require __DIR__ . '/Framework/DomQuery.php';
require __DIR__ . '/Framework/FileMutator.php';
require __DIR__ . '/Framework/Expect.php';
require __DIR__ . '/CodeCoverage/Collector.php';
require __DIR__ . '/Runner/Job.php';

Tester\Environment::setup();
