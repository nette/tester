<?php

use Tester\Assert,
	Tester\Helpers;

require __DIR__ . '/bootstrap.php';


$win = defined('PHP_WINDOWS_VERSION_BUILD');

Assert::same( $win ? '""' : "''", Helpers::escapeArg('') );
Assert::same( $win ? '""' : "''", Helpers::escapeArg(NULL) );
Assert::same( $win ? '"žluťoučký"' : "'žluťoučký'", Helpers::escapeArg('žluťoučký') );
Assert::same( $win ? '"\'""%"' : "''\\''\"%'" , Helpers::escapeArg('\'"%') );
