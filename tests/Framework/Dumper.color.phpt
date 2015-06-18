<?php

use Tester\Assert;
use Tester\Dumper;

require __DIR__ . '/../bootstrap.php';


Assert::match("\x1b[0m", Dumper::color(NULL));
Assert::match("\x1b[1;31m", Dumper::color('red'));
Assert::match("\x1b[1;31;42m", Dumper::color('red/green'));
Assert::match("\x1b[1;31;42m", Dumper::color('red/lime'));

Assert::match("\x1b[0mhello\x1b[0m", Dumper::color(NULL, 'hello'));
Assert::match("\x1b[1;31mhello\x1b[0m", Dumper::color('red', 'hello'));
Assert::match("\x1b[1;31;42mhello\x1b[0m", Dumper::color('red/green', 'hello'));
Assert::match("\x1b[1;31;42mhello\x1b[0m", Dumper::color('red/lime', 'hello'));
