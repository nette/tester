<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Helpers;

require __DIR__ . '/../bootstrap.php';


$win = defined('PHP_WINDOWS_VERSION_BUILD');
setlocale(LC_CTYPE, 'en_US.UTF-8'); // to not strip non-ASCII characters

Assert::same($win ? '""' : "''", Helpers::escapeArg(''));
Assert::same($win ? '"žluťoučký"' : "'žluťoučký'", Helpers::escapeArg('žluťoučký'));
Assert::same($win ? '"\'""%"' : "''\\''\"%'", Helpers::escapeArg('\'"%'));
