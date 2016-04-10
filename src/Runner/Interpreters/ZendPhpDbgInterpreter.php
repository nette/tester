<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner\Interpreters;

use Tester\Helpers;
use Tester\Runner\PhpInterpreter;


/**
 * Zend phpdbg command-line executable.
 */
class ZendPhpDbgInterpreter implements PhpInterpreter
{
	/** @var string  PHP arguments */
	public $arguments;

	/** @var string  PHP executable */
	private $path;

	/** @var string  PHP version */
	private $version;

	/** @var string */
	private $error;


	public function __construct($path, $args = NULL)
	{
		$this->path = Helpers::escapeArg($path);
		$proc = proc_open(
			"$this->path -n $args -V",
			[['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
			$pipes,
			NULL,
			NULL,
			['bypass_shell' => TRUE]
		);
		$output = stream_get_contents($pipes[1]);

		$this->error = trim(stream_get_contents($pipes[2]));
		if (proc_close($proc)) {
			throw new \Exception("Unable to run '$path': " . preg_replace('#[\r\n ]+#', ' ', $this->error));
		} elseif (!preg_match('#^PHP ([\w.-]+)#im', $output, $matches)) {
			throw new \Exception("Unable to detect PHP version (output: $output).");
		} elseif (version_compare($matches[1], '7.0.0', '<')) {
			throw new \Exception('Unable to use phpdbg on PHP < 7.0.0.');
		}

		$this->version = $matches[1];
		$this->arguments = $args;
	}


	/**
	 * @return string
	 */
	public function getCommandLine()
	{
		return $this->path . ' -qrrb -S cli' . $this->arguments;
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
	public function canMeasureCodeCoverage()
	{
		return TRUE;
	}


	/**
	 * @return bool
	 */
	public function isCgi()
	{
		return FALSE;
	}


	/**
	 * @return string
	 */
	public function getStartupError()
	{
		return $this->error;
	}

}
