<?php

use Tester\Assert;
use Tester\FileMutator;

require __DIR__ . '/../bootstrap.php';


FileMutator::register();

require __DIR__ . '/fixtures/final.class.php';

$rc = new ReflectionClass('FinalClass');
Assert::false($rc->isFinal());
Assert::false($rc->getMethod('finalMethod')->isFinal());
