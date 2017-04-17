<?php

/**
 * Test environment initialization.
 */

require_once __DIR__ . '/Framework/Helpers.php';
require_once __DIR__ . '/Framework/Environment.php';
require_once __DIR__ . '/Framework/DataProvider.php';
require_once __DIR__ . '/Framework/Assert.php';
require_once __DIR__ . '/Framework/AssertException.php';
require_once __DIR__ . '/Framework/Dumper.php';
require_once __DIR__ . '/Framework/FileMock.php';
require_once __DIR__ . '/Framework/TestCase.php';
require_once __DIR__ . '/Framework/DomQuery.php';
require_once __DIR__ . '/CodeCoverage/Collector.php';
require_once __DIR__ . '/Runner/Job.php';

Tester\Environment::setup();
