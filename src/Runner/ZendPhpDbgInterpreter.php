<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester\Helpers;


/**
 * Zend phpdbg command-line executable.
 */
class ZendPhpDbgInterpreter implements PhpInterpreter
{
	/** @var string  PHP arguments */
	private $arguments;

	/** @var string  PHP executable */
	private $path;

	/** @var string  PHP version */
	private $version;

	/** @var string */
	private $error;


	public function __construct($path, $args = NULL)
	{
		$this->path = Helpers::escapeArg($path);
		$this->arguments = ' -qrrb -S cli' . $args;

		$proc = proc_open(
			"$this->path -n $this->arguments " . Helpers::escapeArg(__DIR__ . '/info.php') . ' serialized',
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
		} elseif (!($info = @unserialize($output))) {
			throw new \Exception("Unable to detect PHP version (output: $output).");
		}

		$this->version = $info->version;
		if (version_compare($this->version, '7.0.0', '<')) {
			throw new \Exception('Unable to use phpdbg on PHP < 7.0.0.');
		}
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
