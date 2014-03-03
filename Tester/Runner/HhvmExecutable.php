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
	private $arguments = '';

	/** @var string  PHP executable */
	private $path;

	/** @var string  PHP version */
	private $version;


	public function __construct($path, $version, $args = NULL)
	{
		$this->path = $path;
		$this->version = $version;
		$this->arguments = (string) $args;
		$this->addArgument('--php');
	}


	/**
	 * @return string
	 */
	public function getCommandLine()
	{
		return Helpers::escapeArg($this->path) . ($this->arguments !== '' ? ' ' . $this->arguments : '');
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


	/**
	 * @return string
	 */
	public function getArguments()
	{
		return $this->arguments;
	}


	/**
	 * @param string
	 * @param mixed
	 */
	public function addArgument($name, $value = NULL)
	{
		if ($this->arguments !== '') {
			$this->arguments .= ' ';
		}

		$this->arguments .= $name;
		$this->arguments .= ($value !== NULL ? '=' . Helpers::escapeArg($value) : '');
	}
}
