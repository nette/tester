<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester\Helpers;


/**
 * Zend PHP executable command-line.
 *
 * @author     David Grudl
 */
class ZendPhpBinary implements IPhpInterpreter
{
	/** @var string  PHP arguments */
	private $commandLineArgs;

	/** @var string  PHP executable */
	private $path;

	/** @var string  PHP version */
	private $version;

	/** @var bool  is CGI? */
	private $cgi;

	/** @var bool  has Xdebug? */
	private $xdebug;


	/** @var resource */
	private $proc;

	/** @var resource */
	private $stdout;

	/** @var int */
	private $exitCode;

	/** @var string */
	private $output;


	public function __construct($path, $commandLineArgs = NULL)
	{
		$this->path = Helpers::escapeArg($path);
		$proc = @proc_open(
			"$this->path -n $commandLineArgs -v", // -v must be the last
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
		} elseif (!preg_match('#^PHP (\S+).*c(g|l)i#i', $output, $matches)) {
			throw new \Exception("Unable to detect PHP version (output: $output).");
		}

		$this->version = $matches[1];
		$this->cgi = strcasecmp($matches[2], 'g') === 0;
		$this->xdebug = strpos($output, 'Xdebug') > 0;
		$this->commandLineArgs = $commandLineArgs;
	}


	/**
	 * @return string
	 */
	public function getShortInfo()
	{
		return $this->path . ' ' . $this->commandLineArgs;
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
	public function hasXdebug()
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
	 * @param  string
	 * @param  string	 
	 */
	public function setIniValue($name, $value)
	{
		$this->commandLineArgs = trim($this->commandLineArgs . ' -d ' . Helpers::escapeArg("$name=$value"));
	}


	/**
	 * @inherit
	 */
	public function run($file, array $arguments, array $iniValues, array $envVars)
	{
		foreach ($envVars as $name => $value) {
			putenv($name . '=' . $value);
		}

		$cmd = array($this->path);
		$cmd[] = '-n';
		$cmd[] = $this->commandLineArgs;

		foreach ($iniValues as $name => $value) {
			$cmd[] = '-d ' . Helpers::escapeArg("$name=$value");
		}

		$cmd[] = Helpers::escapeArg($file);

		foreach ($arguments as $arg) {
			$cmd[] = Helpers::escapeArg($arg);
		}

		$this->proc = proc_open(
			implode(' ', $cmd),
			array(
				array('pipe', 'r'),
				array('pipe', 'w'),
				array('pipe', 'w'),
			),
			$pipes,
			dirname($file),
			NULL,
			array('bypass_shell' => TRUE)
		);
		list($stdin, $this->stdout, $stderr) = $pipes;
		fclose($stdin);
		fclose($stderr);
		stream_set_blocking($this->stdout, 0);
	}


	/**
	 * @return bool
	 */
	public function isRunning()
	{
		if (!is_resource($this->stdout)) {
			return FALSE;
		}

		$this->output .= stream_get_contents($this->stdout);
		$status = proc_get_status($this->proc);
		if ($status['running']) {
			return TRUE;
		}

		fclose($this->stdout);
		$code = proc_close($this->proc);
		$this->exitCode = $code === -1  /** @todo Where to place exit code constants? */
			? $status['exitcode']
			: $code;

		return FALSE;
	}


	/**
	 * @return array[int exitCode, string stdout]
	 */
	function getResult()
	{
		return array($this->exitCode, $this->output);
	}

}
