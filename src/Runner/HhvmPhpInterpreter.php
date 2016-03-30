<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester\Helpers;


/**
 * HHVM command-line executable.
 */
class HhvmPhpInterpreter implements PhpInterpreter
{
	/** @var string  HHVM arguments */
	private $arguments;

	/** @var string  HHVM executable */
	private $path;

	/** @var string  HHVM version */
	private $version;

	/** @var string  PHP version */
	private $phpVersion;

	/** @var string */
	private $error;


	public function __construct($path, $args = NULL)
	{
		$this->path = Helpers::escapeArg($path);
		$this->arguments = ' --php -n -d hhvm.log.always_log_unhandled_exceptions=false' . $args; // HHVM issue #3019

		$proc = proc_open(
			"$this->path $this->arguments " . Helpers::escapeArg(__DIR__ . '/info.php' . ' serialized'),
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
			throw new \Exception("Unable to detect HHVM version (output: $output).");
		}

		$this->phpVersion = $info->version;
		$this->version = $info->hhvmVersion;
		if (version_compare($this->version, '3.3.0', '<')) {
			throw new \Exception('HHVM below version 3.3.0 is not supported.');
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
		return $this->phpVersion;
	}


	/**
	 * @return bool
	 */
	public function canMeasureCodeCoverage()
	{
		return FALSE;
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
