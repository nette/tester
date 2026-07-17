<?php

/**
 * Minimal HTTP endpoint used by HttpAssert.phpt as a local, dependency-free
 * replacement for httpbin.org. Run via `php -S 127.0.0.1:<port> http-server.php`.
 */

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// /status/<code> - returns the given status code with an empty body
if (preg_match('#^/status/(\d+)$#', $path, $m)) {
	http_response_code((int) $m[1]);
	return;
}

// /response-headers?Name=Value - echoes the query params as response headers
if ($path === '/response-headers') {
	$out = [];
	foreach (explode('&', (string) ($_SERVER['QUERY_STRING'] ?? '')) as $pair) {
		[$name, $value] = array_pad(explode('=', $pair, 2), 2, '');
		[$name, $value] = [urldecode($name), urldecode($value)];
		header("$name: $value");
		$out[$name] = $value;
	}

	echo json_encode($out);
	return;
}

// /redirect - sends a 302 to /get
if ($path === '/redirect') {
	header('Location: /get', true, 302);
	return;
}

// /json - a fixed JSON document
if ($path === '/json') {
	header('Content-Type: application/json');
	echo json_encode(['slideshow' => ['title' => 'Sample Slide Show', 'slides' => []]]);
	return;
}

// anything else - echoes back request details as JSON
$headers = [];
foreach ($_SERVER as $key => $value) {
	if (str_starts_with($key, 'HTTP_')) {
		$name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
		$headers[$name] = $value;
	}
}

$body = json_encode([
	'method' => $_SERVER['REQUEST_METHOD'],
	'url' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
	'args' => (object) [],
	'headers' => $headers,
	'data' => file_get_contents('php://input'),
	'cookies' => (object) $_COOKIE,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

header('Content-Type: application/json');
header('Content-Length: ' . strlen($body));
echo $body;
