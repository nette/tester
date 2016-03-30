<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner\Interpreters;

use Tester\Helpers;
use Tester\Runner\PhpInterpreter;


/**
 * Common functionality for PHP interpreters.
 */
abstract class AbstractInterpreter implements PhpInterpreter
{
	/** @var string  PHP arguments */
	protected $arguments;

	/** @var string  PHP executable */
	protected $path;

	/** @var \stdClass */
	protected $info;

	/** @var string */
	protected $error;


	public function __construct($path, $args = NULL)
	{
		$this->path = Helpers::escapeArg($path);
		$this->arguments = $args;

		$proc = proc_open(
			"$this->path $this->arguments " . Helpers::escapeArg(__DIR__ . '/../info.php') . ' serialized',
			[['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
			$pipes,
			NULL,
			NULL,
			['bypass_shell' => TRUE]
		);
		$output = stream_get_contents($pipes[1]);
		$this->error = trim(stream_get_contents($pipes[2]));

		if (proc_close($proc)) {
			throw new \Exception("Unable to run $this->path: " . preg_replace('#[\r\n ]+#', ' ', $this->error));
		}

		if ($this->isCgi()) {
			list(, $output) = explode("\r\n\r\n", $output, 2);
		}

		if (!($info = @unserialize($output))) {
			throw new \Exception("Unable to detect PHP version (output: $output).");
		}

		$this->info = $info;
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
		return $this->info->version;
	}


	/**
	 * @return bool
	 */
	public function canMeasureCodeCoverage()
	{
		return in_array('xdebug', $this->info->extensions,TRUE);
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
