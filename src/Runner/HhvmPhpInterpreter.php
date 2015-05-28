<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester\Helpers;


/**
 * HHVM command-line executable.
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

	/** @var string */
	private $error;


	public function __construct($path, $args = NULL)
	{
		$this->path = Helpers::escapeArg($path);
		$proc = proc_open(
			"$this->path --php $args -r " . Helpers::escapeArg('echo HHVM_VERSION . "|" . PHP_VERSION;'),
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
		} elseif (count($tmp = explode('|', $output)) !== 2) {
			throw new \Exception("Unable to detect HHVM version (output: $output).");
		}

		list($this->version, $this->phpVersion) = $tmp;
		if (version_compare($this->version, '3.3.0', '<')) {
			throw new \Exception('HHVM below version 3.3.0 is not supported.');
		}
		$this->arguments = ' --php -d hhvm.log.always_log_unhandled_exceptions=false' . ($args ? " $args" : ''); // HHVM issue #3019
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


	/**
	 * @return string
	 */
	public function getErrorOutput()
	{
		return $this->error;
	}

}
