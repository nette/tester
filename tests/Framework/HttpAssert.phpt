<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\AssertException;
use Tester\HttpAssert;

require __DIR__ . '/../bootstrap.php';


// Start a local HTTP server (see fixtures/http-server.php) instead of relying on
// an external service, so the test is fast and deterministic.
$reservePort = function (): int {
	$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
	$port = (int) substr(strrchr(stream_socket_get_name($socket, remote: false), ':'), 1);
	fclose($socket);
	return $port;
};

$port = $reservePort();
$closedPort = $reservePort(); // a port with nothing listening, for the failure test

// Discard the server's request log; an unread pipe would eventually deadlock it.
$devNull = DIRECTORY_SEPARATOR === '\\' ? 'nul' : '/dev/null';
$proc = proc_open(
	[PHP_BINARY, '-S', "127.0.0.1:$port", __DIR__ . '/fixtures/http-server.php'],
	[['file', $devNull, 'r'], ['file', $devNull, 'w'], ['file', $devNull, 'w']],
	$pipes,
);
register_shutdown_function(function () use ($proc) {
	proc_terminate($proc);
	proc_close($proc);
});

$base = "http://127.0.0.1:$port";
for ($i = 0; $i < 100; $i++) {
	if ($conn = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1)) {
		fclose($conn);
		break;
	}
	usleep(50_000);
}


test('Basic GET request', function () use ($base) {
	HttpAssert::fetch("$base/get")
		->expectCode(200)
		->expectHeader('Content-Type', contains: 'json')
		->expectBody(contains: "\"url\": \"$base/get\"");
});

test('Custom headers', function () use ($base) {
	HttpAssert::fetch(
		"$base/headers",
		headers: [
			'X-Test-Header' => 'test-value',
			'User-Agent' => 'Tester/1.0',
		],
	)
		->expectCode(200)
		->expectBody(contains: '"X-Test-Header": "test-value"')
		->expectBody(contains: '"User-Agent": "Tester/1.0"');
});

test('Different HTTP methods', function () use ($base) {
	HttpAssert::fetch(
		"$base/post",
		method: 'POST',
		body: '{"test": "data"}',
	)
		->expectCode(200)
		->expectBody(contains: '"{\"test\": \"data\"}"');

	HttpAssert::fetch("$base/put", method: 'PUT', body: 'test data')
		->expectCode(200)
		->expectBody(contains: '"data": "test data"');

	HttpAssert::fetch("$base/delete", method: 'DELETE')
		->expectCode(200);
});


test('Status code assertions', function () use ($base) {
	HttpAssert::fetch("$base/status/404")
		->expectCode(404)
		->denyCode(200)
		->denyCode(fn($code) => $code >= 200 && $code < 300);

	HttpAssert::fetch("$base/status/200")
		->expectCode(fn($code) => $code >= 200 && $code < 300)
		->denyCode(404);
});

test('expectCode properly fails when code differs', function () use ($base) {
	Assert::exception(
		fn() => HttpAssert::fetch("$base/status/404")->expectCode(200),
		AssertException::class,
		'HTTP status code validation failed: 404 should be 200',
	);
});

test('denyCode properly fails when forbidden code appears', function () use ($base) {
	Assert::exception(
		fn() => HttpAssert::fetch("$base/status/200")->denyCode(200),
		AssertException::class,
		'HTTP status code validation failed: 200 should not be 200',
	);
});


test('Header assertions', function () use ($base) {
	HttpAssert::fetch("$base/response-headers?Content-Type=application/json&X-Custom=test")
		->expectHeader('Content-Type')
		->expectHeader('Content-Type', 'application/json')
		->expectHeader('Content-Type', contains: 'json')
		->expectHeader('Content-Type', matches: '~^application\/json~')
		->expectHeader('X-Custom', 'test')
		->denyHeader('X-NonExistent')
		->denyHeader('Content-Type', 'text/html')
		->denyHeader('Content-Type', contains: 'xml');
});

test('Header existence and non-existence', function () use ($base) {
	HttpAssert::fetch("$base/get")
		->expectHeader('Content-Type')  // Should exist
		->denyHeader('X-NonExistent-Header');  // Should not exist
});

test('expectHeader properly fails when header missing', function () use ($base) {
	Assert::exception(
		fn() => HttpAssert::fetch("$base/get")->expectHeader('X-NonExistent'),
		AssertException::class,
		"Header 'X-NonExistent' should exist",
	);
});

test('expectHeader properly fails when header value differs', function () use ($base) {
	Assert::exception(
		fn() => HttpAssert::fetch("$base/get")->expectHeader('Content-Type', 'text/plain'),
		AssertException::class,
		"Header 'Content-Type' validation failed: 'application/json' should be 'text/plain'",
	);
});

test('Header deny assertions', function () use ($base) {
	HttpAssert::fetch("$base/response-headers?Content-Type=application/json&X-Custom=test")
		->denyHeader('X-NonExistent')  // Header should not exist
		->denyHeader('Content-Type', 'text/html')  // Should not equal
		->denyHeader('Content-Type', contains: 'xml')  // Should not contain
		->denyHeader('X-Custom', matches: '~fail~');  // Should not match pattern
});

test('denyHeader properly fails when header exists', function () use ($base) {
	Assert::exception(
		fn() => HttpAssert::fetch("$base/get")->denyHeader('Content-Type'),
		AssertException::class,
		"Header 'Content-Type' should not exist",
	);
});


test('Body content assertions', function () use ($base) {
	HttpAssert::fetch("$base/get")
		->expectBody(contains: '"args": {}')
		->expectBody(matches: '%A%"url":%A%/get%A%')
		->expectBody(fn($body) => strlen($body) > 100)
		->denyBody(contains: 'error')
		->denyBody(contains: 'password')
		->denyBody(matches: '~error|exception~i');
});

test('expectBody properly fails when content missing', function () use ($base) {
	Assert::exception(
		fn() => HttpAssert::fetch("$base/get")->expectBody(contains: 'nonexistent-content'),
		AssertException::class,
		'Body validation failed: %A%',
	);
});

test('denyBody properly fails when forbidden content appears', function () use ($base) {
	Assert::exception(
		fn() => HttpAssert::fetch("$base/get")->denyBody(contains: '"method"'),
		AssertException::class,
		'Body validation failed: %A%',
	);
});


test('Redirect following', function () use ($base) {
	// Test without following redirects
	HttpAssert::fetch("$base/redirect", follow: false)
		->expectCode(302)
		->expectHeader('Location');

	// Test with following redirects
	HttpAssert::fetch("$base/redirect", follow: true)
		->expectCode(200)
		->expectBody(contains: "\"url\": \"$base/get\"");
});


test('Custom validation functions', function () use ($base) {
	HttpAssert::fetch("$base/get")
		->expectCode(fn($code) => $code >= 200 && $code < 300)
		->expectHeader('Content-Length', fn($header) => is_numeric($header) && (int) $header > 0)
		->expectBody(fn($body) => json_decode($body) !== null)
		->denyCode(fn($code) => $code >= 400)
		->denyBody(fn($body) => str_contains($body, 'error'));
});


test('JSON response validation', function () use ($base) {
	HttpAssert::fetch("$base/json")
		->expectCode(200)
		->expectHeader('Content-Type', contains: 'json')
		->expectBody(fn($body) => json_decode($body) !== null)
		->expectBody(contains: '"slideshow"')
		->expectBody(matches: '%A%"title":%A%');
});


test('Headers array formats', function () use ($base) {
	// Test both header array formats
	HttpAssert::fetch(
		"$base/headers",
		headers: [
			'X-Array-Key' => 'value1',
			'X-Array-Value: value2',
		],
	)
		->expectBody(contains: '"X-Array-Key": "value1"')
		->expectBody(contains: '"X-Array-Value": "value2"');
});


test('Cookies', function () use ($base) {
	HttpAssert::fetch(
		"$base/cookies",
		cookies: [
			'session' => 'abc123',
			'preferences' => 'dark-mode',
		],
	)
		->expectCode(200)
		->expectBody(contains: '"session": "abc123"')
		->expectBody(contains: '"preferences": "dark-mode"');
});


test('Fluent interface chaining', function () use ($base) {
	$test = HttpAssert::fetch("$base/get");

	Assert::type(HttpAssert::class, $test);
	Assert::type(HttpAssert::class, $test->expectCode(200));
	Assert::type(HttpAssert::class, $test->expectHeader('Content-Type'));
	Assert::type(HttpAssert::class, $test->expectBody(contains: '"method"'));
	Assert::type(HttpAssert::class, $test->denyCode(404));
	Assert::type(HttpAssert::class, $test->denyHeader('X-NonExistent'));
	Assert::type(HttpAssert::class, $test->denyBody(contains: 'error'));
});


test('Error handling', function () use ($closedPort) {
	Assert::exception(
		fn() => HttpAssert::fetch("http://127.0.0.1:$closedPort/")->expectCode(200),
		Exception::class,
		'HTTP request failed%A%',
	);
});


test('Multiple expectations on same response', function () use ($base) {
	$test = HttpAssert::fetch("$base/get");

	// Multiple calls work fine since response is already fetched
	$test->expectCode(200);
	$test->expectHeader('Content-Type', contains: 'json');
	$test->expectBody(contains: '"method"');
	$test->denyCode(404);
	$test->denyHeader('X-Nonexistent');
});
