<?php

use Tester\Assert;
use Tester\Environment;

require __DIR__ . '/../bootstrap.php';

Assert::same('1', getenv(Environment::RUNNER));
