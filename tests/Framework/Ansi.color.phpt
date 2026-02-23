<?php declare(strict_types=1);

use Tester\Ansi;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// returns ANSI sequence only
Assert::match("\e[0m", Ansi::color(''));
Assert::match("\e[1m\e[31m", Ansi::color('red'));
Assert::match("\e[1m\e[31m\e[42m", Ansi::color('red/green'));
Assert::match("\e[1m\e[31m\e[42m", Ansi::color('red/lime'));

// wraps text with style and reset
Assert::match("\e[0mhello\e[0m", Ansi::colorize('hello', ''));
Assert::match("\e[1m\e[31mhello\e[0m", Ansi::colorize('hello', 'red'));
Assert::match("\e[1m\e[31m\e[42mhello\e[0m", Ansi::colorize('hello', 'red/green'));
Assert::match("\e[1m\e[31m\e[42mhello\e[0m", Ansi::colorize('hello', 'red/lime'));
