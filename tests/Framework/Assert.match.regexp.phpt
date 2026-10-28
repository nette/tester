<?php declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

// regexp
Assert::match('#A#', 'A');
Assert::match('~A~', 'A');
Assert::match('#A#imsxUu', 'A');

Assert::match("#\r\n#", "\r\n");
Assert::match('#\r\n#', "\r\n");

// mask
Assert::match('/A/', '/A/');
Assert::match('(A)', '(A)');
Assert::match('@A@', '@A@');
Assert::match('#A~', '#A~');
Assert::match('#A#imsxUub', '#A#imsxUub');

// a slash-delimited pattern is a mask, the failure says so
Assert::exception(
	fn() => Assert::match('/^foo$/i', 'foo'),
	Tester\AssertException::class,
	"'foo' should match '/^foo$/i'\n    regular expressions are delimited by ~ or #, so this pattern is a mask",
);

Assert::exception(
	fn() => Assert::match('/var/www/', '/var/log/'),
	Tester\AssertException::class,
	"'/var/log/' should match '/var/www/'\n    regular expressions %a%",
);

Assert::exception(
	fn() => Assert::match('#^foo$#', 'bar'),
	Tester\AssertException::class,
	"'bar' should match '#^foo$#'",
);
