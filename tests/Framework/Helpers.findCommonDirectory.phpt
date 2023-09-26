<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Helpers;

require __DIR__ . '/../bootstrap.php';

Assert::same('', Helpers::findCommonDirectory([]));

Assert::match(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures.helpers', Helpers::findCommonDirectory([
	__DIR__ . '/fixtures.helpers/a',
	__DIR__ . '/fixtures.helpers/aa',
]));

Assert::match(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures.helpers', Helpers::findCommonDirectory([
	__DIR__ . '/fixtures.helpers/a/file.txt',
	__DIR__ . '/fixtures.helpers/aa/file.txt',
]));

Assert::match(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures.helpers' . DIRECTORY_SEPARATOR . 'a', Helpers::findCommonDirectory([
	__DIR__ . '/fixtures.helpers/a/',
	__DIR__ . '/fixtures.helpers/a/file.txt',
]));

Assert::match(getcwd(), Helpers::findCommonDirectory([
	'.',
]));

Assert::match(dirname(getcwd()), Helpers::findCommonDirectory([
	'..',
]));


// Root directories always end by directory separator.
if (is_dir('C:/')) {
	Assert::match('C:\\', Helpers::findCommonDirectory([
		'C:',
	]));
}

if (is_dir('/')) {
	Assert::match(realpath('/'), Helpers::findCommonDirectory([  // realpath() - may point to C:\ in Cygwin
		'/',
	]));
}


Assert::exception(
	fn() => Helpers::findCommonDirectory(['']),
	RuntimeException::class,
	'Path must not be empty.',
);

Assert::exception(
	fn() => Helpers::findCommonDirectory(['does-not-exist']),
	RuntimeException::class,
	"File or directory 'does-not-exist' does not exist.",
);
