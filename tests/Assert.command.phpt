<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$command = defined('PHP_WINDOWS_VERSION_BUILD')
	? 'dir'
	: 'ls';

$output = Assert::command($command);
Assert::match('%A%Assert.command.phpt%A%', $output);

Assert::exception(function() use ($command) {
	Assert::command($command, 10);
}, 'Tester\AssertException', 'Exit code 10 was expected but got 0');
