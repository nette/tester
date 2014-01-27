<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner;


/**
 * PHP executable command-line.
 *
 * @author     David Grudl
 */
class PhpExecutable
{
	/** @var string  PHP arguments */
	public $arguments;

	/** @var string  PHP executable */
	private $path;

	/** @var string  PHP version */
	private $version;

	/** @var bool is CGI? */
	private $cgi;


	public function __construct($path, $args = NULL)
	{
		$this->path = \Tester\Helpers::escapeArg($path);
		$descriptors = array(array('pipe', 'r'), array('pipe', 'w'), array('pipe', 'w'));
		$proc = @proc_open("$this->path -n -v", $descriptors, $pipes);
		$output = stream_get_contents($pipes[1]);
		$error = stream_get_contents($pipes[2]);
		if (proc_close($proc)) {
			throw new \Exception("Unable to run '$path': " . preg_replace('#[\r\n ]+#', ' ', $error));
		} elseif (!preg_match('#^PHP (\S+).*c(g|l)i#i', $output, $matches)) {
			throw new \Exception("Unable to detect PHP version (output: $output).");
		}

		$this->version = $matches[1];
		$this->cgi = strcasecmp($matches[2], 'g') === 0;
		$this->arguments = $args;
	}


	/**
	 * @return string
	 */
	public function getCommandLine()
	{
		return $this->path . ' ' . $this->arguments;
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
