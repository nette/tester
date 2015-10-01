<?php

/**
 * @phpversion 7
 */

use Tester\Assert;
use Tester\Dumper;

require __DIR__ . '/../bootstrap.php';


Assert::match('/* Anonymous class defined in file %a% on line %d% */', Dumper::toPhp(new class {}));
