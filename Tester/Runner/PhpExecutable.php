<?php

/**
 * This file is part of the Nette Tester.
 *
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Tester\Runner;



/**
 * PHP executable command-line.
 *
 * @author     David Grudl
 */
class PhpExecutable
{
	/** @var string  PHP command line */
	private $cmdLine;

	/** @var string  PHP version */
	private $version;

	/** @var bool is CGI? */
	private $cgi;



	public function __construct($path, $args = NULL)
	{
		exec(escapeshellarg($path) . ' -n -v', $output, $res);
		if ($res) {
			throw new \Exception("Unable to execute '$path'.");
		}

		if (!preg_match('#^PHP (\S+).*c(g|l)i#i', $output[0], $matches)) {
			throw new \Exception("Unable to detect PHP version (output: $output[0]).");
		}

		$this->version = $matches[1];
		$this->cgi = strcasecmp($matches[2], 'g') === 0;
		$this->cmdLine = escapeshellarg($path) . ' ' . $args;
	}



	/**
	 * @return string
	 */
	public function getCommandLine()
	{
		return $this->cmdLine;
	}



	/**
	 * @return string
	 */
	public function getVersion()
	{
		return $this->version;
	}



	/**
	 * @return bool
	 */
	public function isCgi()
	{
		return $this->cgi;
	}

}
