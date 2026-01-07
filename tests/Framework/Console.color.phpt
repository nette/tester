<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Console;

require __DIR__ . '/../bootstrap.php';


// color() - returns ANSI sequence only
Assert::match("\e[0m", Console::color(''));
Assert::match("\e[1m\e[31m", Console::color('red'));
Assert::match("\e[1m\e[31m\e[42m", Console::color('red/green'));
Assert::match("\e[1m\e[31m\e[42m", Console::color('red/lime'));

// colorize() - wraps text with color and reset
Assert::match("\e[0mhello\e[0m", Console::colorize('hello', ''));
Assert::match("\e[1m\e[31mhello\e[0m", Console::colorize('hello', 'red'));
Assert::match("\e[1m\e[31m\e[42mhello\e[0m", Console::colorize('hello', 'red/green'));
Assert::match("\e[1m\e[31m\e[42mhello\e[0m", Console::colorize('hello', 'red/lime'));
