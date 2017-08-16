<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Tester\Environment::bypassFinals();

require __DIR__ . '/fixtures/final.class.php';

$rc = new ReflectionClass('FinalClass');
Assert::false($rc->isFinal());
Assert::false($rc->getMethod('finalMethod')->isFinal());
