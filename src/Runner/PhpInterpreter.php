<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester\Helpers;


/**
 * PHP command-line executable.
 */
class PhpInterpreter
{
	/** @var string */
	private $commandLine;

	/** @var bool is CGI? */
	private $cgi;

	/** @var \stdClass  created by info.php */
	private $info;

	/** @var string */
	private $error;


	public function __construct($path, array $args = [])
	{
		$this->commandLine = Helpers::escapeArg($path);
		$proc = @proc_open( // @ is escalated to exception
			$this->commandLine . ' --version',
			[['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
			$pipes,
			NULL,
			NULL,
			['bypass_shell' => TRUE]
		);
		if ($proc === FALSE) {
			throw new \Exception("Cannot run PHP interpreter $path. Use -p option.");
		}
		$output = stream_get_contents($pipes[1]);
		proc_close($proc);

		$args = ' -n ' . implode(' ', array_map(['Tester\Helpers', 'escapeArg'], $args));
		if (preg_match('#HipHop VM#', $output)) {
			$args = ' --php' . $args . ' -d hhvm.log.always_log_unhandled_exceptions=false'; // HHVM issue #3019
		} elseif (strpos($output, 'phpdbg') !== FALSE) {
			$args = ' -qrrb -S cli' . $args;
		}
		$this->commandLine .= $args;

		$proc = proc_open(
			$this->commandLine . ' ' . Helpers::escapeArg(__DIR__ . '/info.php') . ' serialized',
			[['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
			$pipes,
			NULL,
			NULL,
			['bypass_shell' => TRUE]
		);
		$output = stream_get_contents($pipes[1]);
		$this->error = trim(stream_get_contents($pipes[2]));
		if (proc_close($proc)) {
			throw new \Exception("Unable to run $path: " . preg_replace('#[\r\n ]+#', ' ', $this->error));
		}

		$parts = explode("\r\n\r\n", $output, 2);
		$this->cgi = count($parts) === 2;
		if (!($this->info = @unserialize($parts[$this->cgi]))) {
			throw new \Exception("Unable to detect PHP version (output: $output).");

		} elseif ($this->info->hhvmVersion && version_compare($this->info->hhvmVersion, '3.3.0', '<')) {
			throw new \Exception('HHVM below version 3.3.0 is not supported.');

		} elseif ($this->info->phpDbgVersion && version_compare($this->info->version, '7.0.0', '<')) {
			throw new \Exception('Unable to use phpdbg on PHP < 7.0.0.');

		} elseif ($this->cgi && $this->error) {
			$this->error .= "\n(note that PHP CLI generates better error messages)";
		}
	}


	/**
	 * @param  string
	 * @param  string
	 */
	public function addPhpIniOption($name, $value = NULL)
	{
		$this->commandLine .= ' -d ' . Helpers::escapeArg($name . ($value === NULL ? '' : "=$value"));
	}


	/**
	 * @return string
	 */
	public function getCommandLine()
	{
		return $this->commandLine;
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
		return $this->info->canMeasureCodeCoverage;
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


	/**
	 * @return string
	 */
	public function getShortInfo()
	{
		return "PHP {$this->info->version} ({$this->info->sapi})"
			. ($this->info->phpDbgVersion ? "; PHPDBG {$this->info->phpDbgVersion}" : '')
			. ($this->info->hhvmVersion ? "; HHVM {$this->info->hhvmVersion}" : '');
	}


	/**
	 * @param  string
	 * @return bool
	 */
	public function hasExtension($name)
	{
		return in_array(strtolower($name), array_map('strtolower', $this->info->extensions), TRUE);
	}

}
