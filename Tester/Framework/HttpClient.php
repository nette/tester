<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester;


/**
 * HTTP client simulates browser.
 */
class HttpClient
{
	/** @var resource */
	private $curl;

	/** @var string[] */
	private $headers;

	/** @var string */
	private $body;


	/**
	 * @return self
	 */
	public static function load($url, array $postData = NULL)
	{
		$client = new static;
		return $client->execute($url, $postData);
	}


	/**
	 * @return self
	 */
	public static function follow($url, array $postData = NULL)
	{
		$client = new static;
		return $client->followRedirects()->execute($url, $postData);
	}


	public function __construct()
	{
		if (!extension_loaded('curl')) {
			throw new \Exception(__CLASS__ . ' requires PHP extension cURL.');
		}
		$this->curl = curl_init();
		curl_setopt_array($this->curl, array(
			CURLOPT_HEADER => TRUE,
			CURLINFO_HEADER_OUT => TRUE,
			CURLOPT_TIMEOUT => 20,
			CURLOPT_FOLLOWLOCATION => FALSE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_COOKIEJAR => FileMock::create(''),
		));
	}


	/**
	 * Follows 'Location:' headers.
	 * @return self
	 */
	public function followRedirects($state = TRUE)
	{
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, (bool) $state);
		return $this;
	}


	/**
	 * @return self
	 */
	public function execute($url, array $postData = NULL, array $headers = NULL)
	{
		curl_setopt_array($this->curl, array(
			CURLOPT_URL => $url,
			CURLOPT_HTTPHEADER => (array) $headers,
			CURLOPT_POST => FALSE,
		));
		if ($postData) {
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData);
		}

		$response = curl_exec($this->curl);
		if (curl_errno($this->curl)) {
			throw new \Exception('HttpClient: ' . curl_error($this->curl));
		}

		$size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
		$this->headers = explode("\r\n", rtrim(substr($response, 0, $size)));
		$this->body = (string) substr($response, $size);
		return $this;
	}


	/**
	 * Returns the last received HTTP code.
	 * @return int
	 */
	public function getCode()
	{
		return curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
	}


	/**
	 * Returns last loaded URL.
	 * @return string
	 */
	public function getUrl()
	{
		return curl_getinfo($this->curl, CURLINFO_EFFECTIVE_URL);
	}


	/**
	 * Returns URL from header 'Location' when followRedirects() is disabled.
	 * @return string
	 */
	public function getRedirect()
	{
		return curl_getinfo($this->curl, CURLINFO_REDIRECT_URL) ?: NULL;
	}


	/**
	 * Returns number of redirects when followRedirects() is enabled.
	 * @return int
	 */
	public function getRedirectCount()
	{
		return curl_getinfo($this->curl, CURLINFO_REDIRECT_COUNT);
	}


	/**
	 * Returns content type of the requested document.
	 * @return string
	 */
	public function getContentType()
	{
		return curl_getinfo($this->curl, CURLINFO_CONTENT_TYPE);
	}


	/**
	 * Returns the received HTTP headers.
	 * @return string[]
	 */
	public function getHeaders()
	{
		return $this->headers;
	}


	/**
	 * Returns the received document body.
	 * @return string
	 */
	public function getBody()
	{
		return $this->body;
	}


	/**
	 * Converts document to DomQuery.
	 * @return DomQuery
	 */
	public function toDom()
	{
		if (strpos($this->getContentType(), 'xml')) {
			return DomQuery::fromXml($this->body);
		} else {
			return DomQuery::fromHtml($this->body);
		}
	}

}
