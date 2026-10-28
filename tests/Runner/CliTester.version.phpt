<?php declare(strict_types=1);

use Tester\{Assert, Environment, Helpers};

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI !== 'cli') {
	Environment::skip('Requires CLI.');
}

exec(Helpers::escapeArg(PHP_BINARY) . ' ' . Helpers::escapeArg(__DIR__ . '/../../src/tester.php') . ' --version', $output, $code);

Assert::same(0, $code);
Assert::same(['Nette Tester ' . Environment::Version], $output);
