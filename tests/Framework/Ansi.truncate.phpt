<?php

declare(strict_types=1);

use Tester\Ansi;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// truncate() - no truncation needed
Assert::same('hello', Ansi::truncate('hello', 10));
Assert::same('hello', Ansi::truncate('hello', 5)); // exactly at width

// truncate() - basic truncation
Assert::same('hel…', Ansi::truncate('hello', 4));
Assert::same('h…', Ansi::truncate('hello', 2));
Assert::same('hello wor…', Ansi::truncate('hello world', 10));

// truncate() - custom ellipsis
Assert::same('hello...', Ansi::truncate('hello world', 8, '...'));
Assert::same('hell...', Ansi::truncate('hello world', 7, '...'));

// truncate() - unicode text
Assert::same('příl…', Ansi::truncate('příliš', 5));
Assert::same('žlu…', Ansi::truncate('žluťoučký', 4));

// truncate() - text with emoji (emoji is 2-wide)
Assert::same('🍏…', Ansi::truncate('🍏🍎🍏', 4)); // 🍏(2) + …(1) = 3, fits in 4
Assert::same('🍏🍎…', Ansi::truncate('🍏🍎🍏', 5)); // 🍏(2) + 🍎(2) + …(1) = 5, fits in 5
Assert::same('a🍏…', Ansi::truncate('a🍏🍎', 4)); // a(1) + 🍏(2) + …(1) = 4, fits in 4

// truncate() - edge case: maxWidth smaller than ellipsis
Assert::same('…', Ansi::truncate('hello', 1));

// truncate() - empty string
Assert::same('', Ansi::truncate('', 5));
