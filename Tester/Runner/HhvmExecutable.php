<?php

/**
 * This file is part of the Nette Tester.
 *
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Tester\Runner;

use Tester\Helpers;

/**
 * HHVM executable command-line.
 *
 * @author     Michael Moravec
 */
class HhvmExecutable implements IPhpInterpreter
{
	/** @var string  PHP arguments */
	public $arguments;

	/** @var string  PHP executable */
	private $path;

	/** @var string  PHP version */
	private $version;


	public function __construct($path, $args = NULL)
	{
		$descriptors = array(array('pipe', 'r'), array('pipe', 'w'), array('pipe', 'w'));
		$proc = @proc_open(Helpers::escapeArg($path) . ' --php -r "echo PHP_VERSION;"', $descriptors, $pipes);
		$output = stream_get_contents($pipes[1]);
		$error = stream_get_contents($pipes[2]);
		if (proc_close($proc)) {
			throw new \Exception("Unable to run '$path': " . preg_replace('#[\r\n ]+#', ' ', $error));
		}

		$this->version = $output;
		$this->path = Helpers::escapeArg($path);
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
		return FALSE;
	}

}
