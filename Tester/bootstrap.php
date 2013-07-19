<?php

/**
 * Test environment initialization.
 *
 * @author     David Grudl
 */

require __DIR__ . '/Framework/Helpers.php';
require __DIR__ . '/Framework/Environment.php';
require __DIR__ . '/Framework/DataProvider.php';
require __DIR__ . '/Framework/Assert.php';
require __DIR__ . '/Framework/Dumper.php';
require __DIR__ . '/Framework/TestCase.php';
require __DIR__ . '/Framework/DomQuery.php';
require __DIR__ . '/CodeCoverage/Collector.php';
require __DIR__ . '/Runner/Job.php';

Tester\Environment::setup();
