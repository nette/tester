<?php

/**
 * @phpExtension Core, date non-exists
 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::fail('Test should be skipped.');
