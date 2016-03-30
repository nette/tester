<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$interpreter = createInterpreter();

Assert::true($interpreter->hasExtension('DaTe'));
Assert::false($interpreter->hasExtension('foo-bar'));
