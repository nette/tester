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
		$descriptors = array(array('pipe', 'r'), array('pipe', 'w'), array('pipe', 'w'));
		$proc = @proc_open(escapeshellarg($path) . ' -n -v', $descriptors, $pipes, NULL, NULL, array('bypass_shell' => TRUE));
		if (!$proc) {
			throw new \Exception("Unable to execute '$path'.");
		}
		$output = stream_get_contents($pipes[1]);
		proc_close($proc);

		if (!preg_match('#^PHP (\S+).*c(g|l)i#i', $output, $matches)) {
			throw new \Exception("Unable to detect PHP version (output: $output).");
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
