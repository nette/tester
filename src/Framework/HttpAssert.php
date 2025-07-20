<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester;

use function curl_error, curl_exec, curl_getinfo, curl_init, curl_setopt, explode, is_int, is_string, rtrim, str_contains, strtoupper, substr, trim;


/**
 * HTTP testing helpers.
 */
class HttpAssert
{
	private function __construct(
		private string $body,
		private int $code,
		private array $headers,
	) {
	}


	/**
	 * Creates HTTP request, executes it and returns HttpTest instance for chaining expectations.
	 */
	public static function fetch(
		string $url,
		string $method = 'GET',
		array $headers = [],
		array $cookies = [],
		bool $follow = false,
		?string $body = null,
	): self
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

		if ($headers) {
			$headerList = [];
			foreach ($headers as $key => $value) {
				if (is_int($key)) {
					$headerList[] = $value;
				} else {
					$headerList[] = "$key: $value";
				}
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headerList);
		}

		if ($body !== null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

		if ($cookies) {
			$cookieString = '';
			foreach ($cookies as $name => $value) {
				$cookieString .= "$name=$value; ";
			}
			curl_setopt($ch, CURLOPT_COOKIE, rtrim($cookieString, '; '));
		}

		$response = curl_exec($ch);
		if ($response === false) {
			throw new \Exception('HTTP request failed: ' . curl_error($ch));
		}

		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$res = new self(
			substr($response, $headerSize),
			curl_getinfo($ch, CURLINFO_HTTP_CODE),
			[],
		);

		$headerString = substr($response, 0, $headerSize);
		foreach (explode("\r\n", $headerString) as $line) {
			if (str_contains($line, ':')) {
				[$name, $value] = explode(':', $line, 2);
				$res->headers[strtolower(trim($name))] = trim($value);
			}
		}

		return $res;
	}


	/**
	 * Asserts HTTP response code matches expectation.
	 */
	public function expectCode(int|\Closure $expected): self
	{
		if ($expected instanceof \Closure) {
			Assert::true($expected($this->code), 'HTTP status code validation failed');
		} else {
			Assert::same($expected, $this->code, 'HTTP status code validation failed');
		}

		return $this;
	}


	/**
	 * Asserts HTTP response code does not match expectation.
	 */
	public function denyCode(int|\Closure $expected): self
	{
		if ($expected instanceof \Closure) {
			Assert::false($expected($this->code), 'HTTP status code validation failed');
		} else {
			Assert::notSame($expected, $this->code, 'HTTP status code validation failed');
		}

		return $this;
	}


	/**
	 * Asserts HTTP response header matches expectation.
	 */
	public function expectHeader(
		string $name,
		string|\Closure|null $expected = null,
		?string $contains = null,
		?string $matches = null,
	): self
	{
		$headerValue = $this->headers[strtolower($name)] ?? null;
		if (!isset($headerValue)) {
			Assert::fail("Header '$name' should exist");
		} elseif (is_string($expected)) {
			Assert::same($expected, $headerValue, "Header '$name' validation failed");
		} elseif ($expected instanceof \Closure) {
			Assert::true($expected($headerValue), "Header '$name' validation failed");
		} elseif ($contains !== null) {
			Assert::contains($contains, $headerValue, "Header '$name' validation failed");
		} elseif ($matches !== null) {
			Assert::match($matches, $headerValue, "Header '$name' validation failed");
		}

		return $this;
	}


	/**
	 * Asserts HTTP response header does not match expectation.
	 */
	public function denyHeader(
		string $name,
		string|\Closure|null $expected = null,
		?string $contains = null,
		?string $matches = null,
	): self
	{
		$headerValue = $this->headers[strtolower($name)] ?? null;
		if (!isset($headerValue)) {
			return $this;
		}

		if (is_string($expected)) {
			Assert::notSame($expected, $headerValue, "Header '$name' validation failed");
		} elseif ($expected instanceof \Closure) {
			Assert::falsey($expected($headerValue), "Header '$name' validation failed");
		} elseif ($contains !== null) {
			Assert::notContains($contains, $headerValue, "Header '$name' validation failed");
		} elseif ($matches !== null) {
			Assert::notMatch($matches, $headerValue, "Header '$name' validation failed");
		} else {
			Assert::fail("Header '$name' should not exist");
		}

		return $this;
	}


	/**
	 * Asserts HTTP response body matches expectation.
	 */
	public function expectBody(
		string|\Closure|null $expected = null,
		?string $contains = null,
		?string $matches = null,
	): self
	{
		if (is_string($expected)) {
			Assert::same($expected, $this->body, 'Body validation failed');
		} elseif ($expected instanceof \Closure) {
			Assert::true($expected($this->body), 'Body validation failed');
		} elseif ($contains !== null) {
			Assert::contains($contains, $this->body, 'Body validation failed');
		} elseif ($matches !== null) {
			Assert::match($matches, $this->body, 'Body validation failed');
		}

		return $this;
	}


	/**
	 * Asserts HTTP response body does not match expectation.
	 */
	public function denyBody(
		string|\Closure|null $expected = null,
		?string $contains = null,
		?string $matches = null,
	): self
	{
		if (is_string($expected)) {
			Assert::notSame($expected, $this->body, 'Body validation failed');
		} elseif ($expected instanceof \Closure) {
			Assert::falsey($expected($this->body), 'Body validation failed');
		} elseif ($contains !== null) {
			Assert::notContains($contains, $this->body, 'Body validation failed');
		} elseif ($matches !== null) {
			Assert::notMatch($matches, $this->body, 'Body validation failed');
		}
		return $this;
	}
}
