<?php

use Tester\Assert,
	Tester\Helpers;

require __DIR__ . '/bootstrap.php';


$win = DIRECTORY_SEPARATOR === '\\';

Assert::same( $win ? '""' : "''", Helpers::escapeArg('') );
Assert::same( $win ? '""' : "''", Helpers::escapeArg(NULL) );
Assert::same( $win ? '"žluťoučký"' : "'žluťoučký'", Helpers::escapeArg('žluťoučký') );
Assert::same( $win ? '"\'""%"' : "''\\''\"%'" , Helpers::escapeArg('\'"%') );
