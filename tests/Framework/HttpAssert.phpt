<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\AssertException;
use Tester\HttpAssert;

require __DIR__ . '/../bootstrap.php';


test('Basic GET request', function () {
	// Test against httpbin.org which provides stable testing endpoints
	HttpAssert::fetch('https://httpbin.org/get')
		->expectCode(200)
		->expectHeader('Content-Type', contains: 'json')
		->expectBody(contains: '"url": "https://httpbin.org/get"');
});

test('Custom headers', function () {
	HttpAssert::fetch(
		'https://httpbin.org/headers',
		headers: [
			'X-Test-Header' => 'test-value',
			'User-Agent' => 'Tester/1.0',
		],
	)
		->expectCode(200)
		->expectBody(contains: '"X-Test-Header": "test-value"')
		->expectBody(contains: '"User-Agent": "Tester/1.0"');
});

test('Different HTTP methods', function () {
	HttpAssert::fetch(
		'https://httpbin.org/post',
		method: 'POST',
		body: '{"test": "data"}',
	)
		->expectCode(200)
		->expectBody(contains: '"{\"test\": \"data\"}"');

	HttpAssert::fetch('https://httpbin.org/put', method: 'PUT', body: 'test data')
		->expectCode(200)
		->expectBody(contains: '"test data": ""');

	HttpAssert::fetch('https://httpbin.org/delete', method: 'DELETE')
		->expectCode(200);
});


test('Status code assertions', function () {
	HttpAssert::fetch('https://httpbin.org/status/404')
		->expectCode(404)
		->denyCode(200)
		->denyCode(fn($code) => $code >= 200 && $code < 300);

	HttpAssert::fetch('https://httpbin.org/status/200')
		->expectCode(fn($code) => $code >= 200 && $code < 300)
		->denyCode(404);
});

test('expectCode properly fails when code differs', function () {
	Assert::exception(
		fn() => HttpAssert::fetch('https://httpbin.org/status/404')->expectCode(200),
		AssertException::class,
		'HTTP status code validation failed: 404 should be 200',
	);
});

test('denyCode properly fails when forbidden code appears', function () {
	Assert::exception(
		fn() => HttpAssert::fetch('https://httpbin.org/status/200')->denyCode(200),
		AssertException::class,
		'HTTP status code validation failed: 200 should not be 200',
	);
});


test('Header assertions', function () {
	HttpAssert::fetch('https://httpbin.org/response-headers?Content-Type=application/json&X-Custom=test')
		->expectHeader('Content-Type')
		->expectHeader('Content-Type', 'application/json')
		->expectHeader('Content-Type', contains: 'json')
		->expectHeader('Content-Type', matches: '~^application\/json~')
		->expectHeader('X-Custom', 'test')
		->denyHeader('X-NonExistent')
		->denyHeader('Content-Type', 'text/html')
		->denyHeader('Content-Type', contains: 'xml');
});

test('Header existence and non-existence', function () {
	HttpAssert::fetch('https://httpbin.org/get')
		->expectHeader('Content-Type')  // Should exist
		->denyHeader('X-NonExistent-Header');  // Should not exist
});

test('expectHeader properly fails when header missing', function () {
	Assert::exception(
		fn() => HttpAssert::fetch('https://httpbin.org/get')->expectHeader('X-NonExistent'),
		AssertException::class,
		"Header 'X-NonExistent' should exist",
	);
});

test('expectHeader properly fails when header value differs', function () {
	Assert::exception(
		fn() => HttpAssert::fetch('https://httpbin.org/get')->expectHeader('Content-Type', 'text/plain'),
		AssertException::class,
		"Header 'Content-Type' validation failed: 'application/json' should be 'text/plain'",
	);
});

test('Header deny assertions', function () {
	HttpAssert::fetch('https://httpbin.org/response-headers?Content-Type=application/json&X-Custom=test')
		->denyHeader('X-NonExistent')  // Header should not exist
		->denyHeader('Content-Type', 'text/html')  // Should not equal
		->denyHeader('Content-Type', contains: 'xml')  // Should not contain
		->denyHeader('X-Custom', matches: '~fail~');  // Should not match pattern
});

test('denyHeader properly fails when header exists', function () {
	Assert::exception(
		fn() => HttpAssert::fetch('https://httpbin.org/get')->denyHeader('Content-Type'),
		AssertException::class,
		"Header 'Content-Type' should not exist",
	);
});


test('Body content assertions', function () {
	HttpAssert::fetch('https://httpbin.org/get')
		->expectBody(contains: '"args": {}')
		->expectBody(matches: '%A%"url":%a%httpbin%A%')
		->expectBody(fn($body) => strlen($body) > 100)
		->denyBody(contains: 'error')
		->denyBody(contains: 'password')
		->denyBody(matches: '~error|exception~i');
});

test('expectBody properly fails when content missing', function () {
	Assert::exception(
		fn() => HttpAssert::fetch('https://httpbin.org/get')->expectBody(contains: 'nonexistent-content'),
		AssertException::class,
		'Body validation failed: %A%',
	);
});

test('denyBody properly fails when forbidden content appears', function () {
	Assert::exception(
		fn() => HttpAssert::fetch('https://httpbin.org/get')->denyBody(contains: 'httpbin.org'),
		AssertException::class,
		'Body validation failed: %A%',
	);
});


test('Redirect following', function () {
	// Test without following redirects
	HttpAssert::fetch('https://httpbin.org/redirect/1', follow: false)
		->expectCode(302)
		->expectHeader('Location');

	// Test with following redirects
	HttpAssert::fetch('https://httpbin.org/redirect/1', follow: true)
		->expectCode(200)
		->expectBody(contains: '"url": "https://httpbin.org/get"');
});


test('Custom validation functions', function () {
	HttpAssert::fetch('https://httpbin.org/get')
		->expectCode(fn($code) => $code >= 200 && $code < 300)
		->expectHeader('Content-Length', fn($header) => is_numeric($header) && (int) $header > 0)
		->expectBody(fn($body) => json_decode($body) !== null)
		->denyCode(fn($code) => $code >= 400)
		->denyBody(fn($body) => str_contains($body, 'error'));
});


test('JSON response validation', function () {
	HttpAssert::fetch('https://httpbin.org/json')
		->expectCode(200)
		->expectHeader('Content-Type', contains: 'json')
		->expectBody(fn($body) => json_decode($body) !== null)
		->expectBody(contains: '"slideshow"')
		->expectBody(matches: '%A%"title":%A%');
});


test('Headers array formats', function () {
	// Test both header array formats
	HttpAssert::fetch(
		'https://httpbin.org/headers',
		headers: [
			'X-Array-Key' => 'value1',
			'X-Array-Value: value2',
		],
	)
		->expectBody(contains: '"X-Array-Key": "value1"')
		->expectBody(contains: '"X-Array-Value": "value2"');
});


test('Cookies', function () {
	HttpAssert::fetch(
		'https://httpbin.org/cookies',
		cookies: [
			'session' => 'abc123',
			'preferences' => 'dark-mode',
		],
	)
		->expectCode(200)
		->expectBody(contains: '"session": "abc123"')
		->expectBody(contains: '"preferences": "dark-mode"');
});


test('Fluent interface chaining', function () {
	$test = HttpAssert::fetch('https://httpbin.org/get');

	Assert::type(HttpAssert::class, $test);
	Assert::type(HttpAssert::class, $test->expectCode(200));
	Assert::type(HttpAssert::class, $test->expectHeader('Content-Type'));
	Assert::type(HttpAssert::class, $test->expectBody(contains: 'httpbin'));
	Assert::type(HttpAssert::class, $test->denyCode(404));
	Assert::type(HttpAssert::class, $test->denyHeader('X-NonExistent'));
	Assert::type(HttpAssert::class, $test->denyBody(contains: 'error'));
});


test('Error handling', function () {
	Assert::exception(
		fn() => HttpAssert::fetch('https://nonexistent-domain-12345.com/test')->expectCode(200),
		Exception::class,
		'HTTP request failed%A%',
	);
});


test('Multiple expectations on same response', function () {
	$test = HttpAssert::fetch('https://httpbin.org/get');

	// Multiple calls work fine since response is already fetched
	$test->expectCode(200);
	$test->expectHeader('Content-Type', contains: 'json');
	$test->expectBody(contains: 'httpbin');
	$test->denyCode(404);
	$test->denyHeader('X-Nonexistent');
});
