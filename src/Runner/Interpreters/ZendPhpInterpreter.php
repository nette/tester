<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner\Interpreters;

use Tester\Helpers;
use Tester\Runner\PhpInterpreter;


/**
 * Zend PHP command-line executable.
 */
class ZendPhpInterpreter implements PhpInterpreter
{
	/** @var string  PHP arguments */
	private $arguments;

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
		$this->arguments = $args;

		$proc = proc_open(
			"$this->path -n $this->arguments " . Helpers::escapeArg(__DIR__ . '/../info.php') . ' serialized',
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
		}

		$this->cgi = stripos($output, 'cgi') !== FALSE;
		if ($this->cgi) {
			list(, $output) = explode("\r\n\r\n", $output, 2);
		}

		if (!($info = @unserialize($output))) {
			throw new \Exception("Unable to detect PHP version (output: $output).");
		}

		$this->version = $info->version;
		$this->xdebug = in_array('xdebug', $info->extensions, TRUE);
	}


	/**
	 * @param  string
	 * @param  string
	 */
	public function addPhpIniOption($name, $value = NULL)
	{
		$this->arguments .= ' -d ' . Helpers::escapeArg($name . ($value === NULL ? '' : "=$value"));
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
	public function canMeasureCodeCoverage()
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
	public function getStartupError()
	{
		return $this->error;
	}

}
