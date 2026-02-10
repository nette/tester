<?php declare(strict_types=1);

/**
 * PHPStan type tests.
 */

use Tester\Assert;
use Tester\DataProvider;
use Tester\DomQuery;
use Tester\Helpers;
use Tester\Runner\Job;
use function PHPStan\Testing\assertType;


/** @param \Closure(): int $closure */
function testAssertWithIntReturn(object $obj, Closure $closure): void
{
	$result = Assert::with($obj, $closure);
	assertType('int', $result);
}


/** @param \Closure(): string $closure */
function testAssertWithStringReturn(object $obj, Closure $closure): void
{
	$result = Assert::with($obj, $closure);
	assertType('string', $result);
}


function testAssertException(): void
{
	$result = Assert::exception(fn() => null, RuntimeException::class);
	assertType('RuntimeException|null', $result);
}


function testAssertThrows(): void
{
	$result = Assert::throws(fn() => null, InvalidArgumentException::class);
	assertType('InvalidArgumentException|null', $result);
}


function testDomQueryFind(): void
{
	$dom = DomQuery::fromHtml('<div>test</div>');
	$result = $dom->find('div');
	assertType('list<Tester\DomQuery>', $result);
}


function testDataProviderParseAnnotation(): void
{
	$result = DataProvider::parseAnnotation('file.ini, query', '/path/to/test.phpt');
	assertType('array{string, string, bool}', $result);
}


function testAssertExpandMatchingPatterns(): void
{
	$result = Assert::expandMatchingPatterns('pattern', 'actual');
	assertType('array{string, string}', $result);
}


function testHelpersParseDocComment(): void
{
	$result = Helpers::parseDocComment('/** @param string $x */');
	assertType('array<array<string>|string>', $result);
}


function testJobGetHeaders(Job $job): void
{
	$result = $job->getHeaders();
	assertType('array<string, string>', $result);
}
