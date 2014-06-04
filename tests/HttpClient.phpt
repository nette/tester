<?php

/**
 * @phpversion 5.4
 */

use Tester\Assert,
	Tester\HttpClient;

require __DIR__ . '/bootstrap.php';


if (!extension_loaded('curl')) {
	Tester\Environment::skip('Requires cURL extension.');
}

$server = @proc_open(
	'php  -S localhost:8000',
	[['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
	$pipes,
	__DIR__ . '/website',
	NULL,
	['bypass_shell' => TRUE]
);
sleep(1);

if (!$server || !proc_get_status($server)['running']) {
	Tester\Environment::skip('Unable to start PHP server.');
}



test(function() { // GET
	$client = HttpClient::load('http://localhost:8000/hello.php');
	Assert::same( 200, $client->getCode() );
	Assert::same( 'http://localhost:8000/hello.php', $client->getUrl() );
	Assert::same( NULL, $client->getRedirect() );
	Assert::same( 0, $client->getRedirectCount() );
	Assert::same( 'text/html; charset=windows-1250', $client->getContentType() );

	$headers = $client->getHeaders();
	Assert::same( 'HTTP/1.1 200 OK', $headers[0] );
	Assert::same( 'X-Love: Nette', end($headers) );
	Assert::match( '<h1>Hello GET</h1>%A%Data: [].', $client->getBody() );

	$q = $client->toDom();
	Assert::type( 'Tester\DomQuery', $q );
	Assert::true( $q->has('h1') );
});


test(function() { // no-followed redirect
	$client = HttpClient::load('http://localhost:8000/redirect.php');
	Assert::same( 302, $client->getCode() );
	Assert::same( 'http://localhost:8000/redirect.php', $client->getUrl() );
	Assert::same( 'http://localhost:8000/hello.php', $client->getRedirect() );
	Assert::same( 0, $client->getRedirectCount() );

	$headers = $client->getHeaders();
	Assert::same( 'HTTP/1.1 302 Found', $headers[0] );
	Assert::match( 'Redirect', $client->getBody() );
});


test(function() { // followed redirect
	$client = HttpClient::follow('http://localhost:8000/redirect.php');
	Assert::same( 200, $client->getCode() );
	Assert::same( 'http://localhost:8000/hello.php', $client->getUrl() );
	Assert::same( NULL, $client->getRedirect() );
	Assert::same( 1, $client->getRedirectCount() );

	$headers = $client->getHeaders();
	Assert::same( 'HTTP/1.1 302 Found', $headers[0] );
	Assert::contains( 'HTTP/1.1 200 OK', $headers );
	Assert::match( '<h1>Hello GET</h1>%A%Data: [].', $client->getBody() );
});


test(function() { // POST
	$client = HttpClient::load('http://localhost:8000/hello.php', ['key' => 'data']);
	Assert::same( 200, $client->getCode() );
	Assert::same( 'http://localhost:8000/hello.php', $client->getUrl() );
	Assert::same( NULL, $client->getRedirect() );
	Assert::same( 0, $client->getRedirectCount() );
	Assert::same( 'text/html; charset=windows-1250', $client->getContentType() );

	$headers = $client->getHeaders();
	Assert::same( 'HTTP/1.1 200 OK', $headers[0] );
	Assert::notSame( '', end($headers) );
	Assert::match( '<h1>Hello POST</h1>%A%Data: {"key":"data"}.', $client->getBody() );
});


test(function() { // 404
	$client = HttpClient::load('http://localhost:8000/unknown.php');
	Assert::same( 404, $client->getCode() );
	Assert::same( 'http://localhost:8000/unknown.php', $client->getUrl() );
	Assert::same( NULL, $client->getRedirect() );
	Assert::same( 0, $client->getRedirectCount() );
	$headers = $client->getHeaders();
	Assert::same( 'HTTP/1.1 404 Not Found', $headers[0] );
});


test(function() { // session
	$client = HttpClient::load('http://localhost:8000/cookie.php');
	Assert::match( '1', $client->getBody() );
	
	$client->execute('http://localhost:8000/cookie.php');
	Assert::match( '2', $client->getBody() );
});


proc_terminate($server);
