<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester\Helpers;

/**
 * PHP executable command-line.
 *
 * @author     David Grudl
 */
class ZendPhpExecutable implements IPhpInterpreter
{
	/** @var string  PHP arguments */
	private $arguments = '';

	/** @var string  PHP executable */
	private $path;

	/** @var string  PHP version */
	private $version;

	/** @var bool is CGI? */
	private $cgi;


	public function __construct($path, $version, $cgi, $args = NULL)
	{
		$this->path = $path;
		$this->version = $version;
		$this->cgi = (bool) $cgi;
		$this->arguments = (string) $args;
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
		return $this->cgi;
	}

	/**
	 * @return array
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
