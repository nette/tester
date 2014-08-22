<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner;


/**
 * HHVM command-line executable.
 *
 * @author  Michael Moravec
 */
class HhvmPhpInterpreter implements PhpInterpreter
{
	/** @var string  HHVM arguments */
	public $arguments;

	/** @var string  HHVM executable */
	private $path;

	/** @var string  HHVM version */
	private $version;

	/** @var string  PHP version */
	private $phpVersion;


	public function __construct($path, $args = NULL)
	{
		$this->path = \Tester\Helpers::escapeArg($path);
		$proc = @proc_open(
			"$this->path --php $args --version", // --version must be the last
			array(array('pipe', 'r'), array('pipe', 'w'), array('pipe', 'w')),
			$pipes,
			NULL,
			NULL,
			array('bypass_shell' => TRUE)
		);
		$output = stream_get_contents($pipes[1]);
		$error = stream_get_contents($pipes[2]);
		if (proc_close($proc)) {
			throw new \Exception("Unable to run '$path': " . preg_replace('#[\r\n ]+#', ' ', $error));
		} elseif (!preg_match('#^HipHop VM (\S+)#i', $output, $matches)) {
			throw new \Exception("Unable to detect HHVM version (output: $output).");
		}

		$this->version = $matches[1];
		$this->arguments = '--php' . ($args ? " $args" : '');

		if (version_compare($this->version, '3.0.0', '<')) {
			throw new \Exception('Hip Hop VM below version 3.0.0 is not supported.');
		}

		// PHP version of HHVM must be obtained another way...

		$proc = @proc_open(
			"$this->path --php -r 'echo PHP_VERSION;'",
			array(array('pipe', 'r'), array('pipe', 'w'), array('pipe', 'w')),
			$pipes,
			NULL,
			NULL,
			array('bypass_shell' => TRUE)
		);
		$output = stream_get_contents($pipes[1]);
		$error = stream_get_contents($pipes[2]);
		if (proc_close($proc)) {
			throw new \Exception("Unable to run '$path': " . preg_replace('#[\r\n ]+#', ' ', $error));
		}

		$this->phpVersion = trim($output);
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
		return $this->phpVersion;
	}


	/**
	 * @return bool
	 */
	public function hasXdebug()
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

}
