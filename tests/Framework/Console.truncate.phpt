<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Console;

require __DIR__ . '/../bootstrap.php';


// truncate() - no truncation needed
Assert::same('hello', Console::truncate('hello', 10));
Assert::same('hello', Console::truncate('hello', 5)); // exactly at width

// truncate() - basic truncation
Assert::same('hel…', Console::truncate('hello', 4));
Assert::same('h…', Console::truncate('hello', 2));
Assert::same('hello wor…', Console::truncate('hello world', 10));

// truncate() - custom ellipsis
Assert::same('hello...', Console::truncate('hello world', 8, '...'));
Assert::same('hell...', Console::truncate('hello world', 7, '...'));

// truncate() - unicode text
Assert::same('příl…', Console::truncate('příliš', 5));
Assert::same('žlu…', Console::truncate('žluťoučký', 4));

// truncate() - text with emoji (emoji is 2-wide)
Assert::same('🍏…', Console::truncate('🍏🍎🍏', 4)); // 🍏(2) + …(1) = 3, fits in 4
Assert::same('🍏🍎…', Console::truncate('🍏🍎🍏', 5)); // 🍏(2) + 🍎(2) + …(1) = 5, fits in 5
Assert::same('a🍏…', Console::truncate('a🍏🍎', 4)); // a(1) + 🍏(2) + …(1) = 4, fits in 4

// truncate() - edge case: maxWidth smaller than ellipsis
Assert::same('…', Console::truncate('hello', 1));

// truncate() - empty string
Assert::same('', Console::truncate('', 5));
