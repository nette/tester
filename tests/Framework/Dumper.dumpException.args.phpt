<?php declare(strict_types=1);

use Tester\Ansi;
use Tester\Assert;
use Tester\AssertException;

require __DIR__ . '/../bootstrap.php';


// Environment::setup() (called from bootstrap) keeps call arguments in stack traces.
Assert::same('0', ini_get('zend.exception_ignore_args'));


function failingHelper(string $url, int $code, array $data, object $obj): void
{
	Assert::same(301, $code);
}


test('stack trace shows call arguments', function () {
	$e = Assert::exception(
		fn() => failingHelper('https://example.com/page', 302, ['a' => 1, 'b' => 2], new stdClass),
		AssertException::class,
	);
	$dump = Ansi::stripAnsi(Tester\Dumper::dumpException($e));
	Assert::contains("failingHelper('https://example.com/page', 302, ['a' => 1, 'b' => 2], stdClass(#", $dump);
});


test('long values are shortened, extreme argument counts are capped', function () {
	// each value is shortened by toLine(): long string -> 70 chars + '...', array -> [...], object -> Class(#hash)
	$e = Assert::exception(
		fn() => failingHelper(str_repeat('x', 500), 302, [str_repeat('y', 500)], new stdClass),
		AssertException::class,
	);
	$dump = Ansi::stripAnsi(Tester\Dumper::dumpException($e));
	Assert::contains("failingHelper('" . str_repeat('x', 70) . "...', 302, ['" . str_repeat('y', 70) . "...'], stdClass(#", $dump);

	// a huge argument list is truncated with a trailing '...'
	$e = Assert::exception(
		fn() => manyArgs(...range(1, 200)),
		AssertException::class,
	);
	$dump = Ansi::stripAnsi(Tester\Dumper::dumpException($e));
	Assert::match('%A%manyArgs(1, 2, 3, %a%, ...)%A%', $dump);
});


function manyArgs(int ...$a): void
{
	Assert::fail('boom');
}
