<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester\Helpers;


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
		$this->path = Helpers::escapeArg($path);
		$proc = proc_open(
			"$this->path -n $args --version", // --version must be the last
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
		} elseif (!preg_match('#^(phpdbg .*\n)?PHP ([^\s,]+)#i', $output, $matches)) {
			throw new \Exception("Unable to detect PHP version (output: $output).");
		}

		$this->version = $matches[2];
		if ($matches[1]) {
			if (version_compare($this->version, '7.0.0', '<')) {
				throw new \Exception('PHP 7.0.0+ is required to use phpdbg.');
			}

			$this->cgi = FALSE;
			$this->arguments = ' -qrrb -S cli' . $args;
			$this->xdebug = FALSE;

		} else {
			$this->cgi = stripos($output, 'cgi') !== FALSE;
			$this->arguments = $args;

			$job = new Job(__DIR__ . '/info.php', $this, array('xdebug'));
			$job->run();
			$this->xdebug = !$job->getExitCode();
		}
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
