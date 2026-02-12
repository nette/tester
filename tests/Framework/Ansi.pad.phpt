<?php

declare(strict_types=1);

use Tester\Ansi;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// pad() - right (default)
Assert::same('hello     ', Ansi::pad('hello', 10));
Assert::same('hello     ', Ansi::pad('hello', 10, ' ', STR_PAD_RIGHT));

// pad() - left
Assert::same('     hello', Ansi::pad('hello', 10, ' ', STR_PAD_LEFT));

// pad() - center (both)
Assert::same('  hello   ', Ansi::pad('hello', 10, ' ', STR_PAD_BOTH));
Assert::same('   hi   ', Ansi::pad('hi', 8, ' ', STR_PAD_BOTH)); // odd padding: 3 left, 3 right
Assert::same('  hi   ', Ansi::pad('hi', 7, ' ', STR_PAD_BOTH)); // odd padding: 2 left, 3 right

// pad() - text already at width
Assert::same('hello', Ansi::pad('hello', 5));
Assert::same('hello', Ansi::pad('hello', 3)); // over width, no change

// pad() - unicode padding character
Assert::same('hello─────', Ansi::pad('hello', 10, '─'));
Assert::same('─────hello', Ansi::pad('hello', 10, '─', STR_PAD_LEFT));
Assert::same('──hello───', Ansi::pad('hello', 10, '─', STR_PAD_BOTH));

// pad() - text with emoji (emoji is 2-wide)
Assert::same('🍏×5    ', Ansi::pad('🍏×5', 8)); // 🍏(2) + ×(1) + 5(1) = 4 width, need 4 spaces
Assert::same('    🍏×5', Ansi::pad('🍏×5', 8, ' ', STR_PAD_LEFT));

// pad() - empty string
Assert::same('     ', Ansi::pad('', 5));
