<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner;


/**
 * Zend PHP command-line executable.
 */
class ZendPhpInterpreter implements PhpInterpreter
{
	/** @var string  PHP arguments */
	public $arguments;

	/** @var string  PHP executable */
	private $path;

	/** @var string  PHP version */
	private $version;

	/** @var bool is CGI? */
	private $cgi;

	/** @var bool */
	private $xdebug;

	/** @var string */
	private $error;


	public function __construct($path, $args = NULL)
	{
		$this->path = \Tester\Helpers::escapeArg($path);
		$proc = proc_open(
			"$this->path -n $args -v", // -v must be the last
			array(array('pipe', 'r'), array('pipe', 'w'), array('pipe', 'w')),
			$pipes,
			NULL,
			NULL,
			array('bypass_shell' => TRUE)
		);
		$output = stream_get_contents($pipes[1]);
		$this->error = trim(stream_get_contents($pipes[2]));
		if (proc_close($proc)) {
			throw new \Exception("Unable to run '$path': " . preg_replace('#[\r\n ]+#', ' ', $this->error));
		} elseif (!preg_match('#^PHP (\S+).*c(g|l)i#i', $output, $matches)) {
			throw new \Exception("Unable to detect PHP version (output: $output).");
		}

		$this->version = $matches[1];
		$this->cgi = strcasecmp($matches[2], 'g') === 0;
		$this->arguments = $args;

		$job = new Job(__DIR__ . '/info.php', $this, array('xdebug'));
		$job->run();
		$this->xdebug = !$job->getExitCode();
	}


	/**
	 * @return string
	 */
	public function getCommandLine()
	{
		return $this->path . $this->arguments;
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
	public function hasXdebug()
	{
		return $this->xdebug;
	}


	/**
	 * @return bool
	 */
	public function isCgi()
	{
		return $this->cgi;
	}


	/**
	 * @return string
	 */
	public function getErrorOutput()
	{
		return $this->error;
	}

}
