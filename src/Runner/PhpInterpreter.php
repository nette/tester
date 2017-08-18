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
			null,
			null,
			['bypass_shell' => true]
		);
		if ($proc === false) {
			throw new \Exception("Cannot run PHP interpreter $path. Use -p option.");
		}
		fclose($pipes[0]);
		$output = stream_get_contents($pipes[1]);
		proc_close($proc);

		$args = ' ' . implode(' ', array_map(['Tester\Helpers', 'escapeArg'], $args));
		if (strpos($output, 'phpdbg') !== false) {
			$args = ' -qrrb -S cli' . $args;
		}
		$this->commandLine .= rtrim($args);

		$proc = proc_open(
			$this->commandLine . ' ' . Helpers::escapeArg(__DIR__ . '/info.php') . ' serialized',
			[['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
			$pipes,
			null,
			null,
			['bypass_shell' => true]
		);
		$output = stream_get_contents($pipes[1]);
		$this->error = trim(stream_get_contents($pipes[2]));
		if (proc_close($proc)) {
			throw new \Exception("Unable to run $path: " . preg_replace('#[\r\n ]+#', ' ', $this->error));
		}

		$parts = explode("\r\n\r\n", $output, 2);
		$this->cgi = count($parts) === 2;
		$this->info = @unserialize(strstr($parts[$this->cgi], 'O:8:"stdClass"'));
		$this->error .= strstr($parts[$this->cgi], 'O:8:"stdClass"', true);
		if (!$this->info) {
			throw new \Exception("Unable to detect PHP version (output: $output).");

		} elseif ($this->info->phpDbgVersion && version_compare($this->info->version, '7.0.0', '<')) {
			throw new \Exception('Unable to use phpdbg on PHP < 7.0.0.');

		} elseif ($this->cgi && $this->error) {
			$this->error .= "\n(note that PHP CLI generates better error messages)";
		}
	}


	/**
	 * @param  string
	 * @param  string
	 * @return static
	 */
	public function withPhpIniOption($name, $value = null)
	{
		$me = clone $this;
		$me->commandLine .= ' -d ' . Helpers::escapeArg($name . ($value === null ? '' : "=$value"));
		return $me;
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
			. ($this->info->phpDbgVersion ? "; PHPDBG {$this->info->phpDbgVersion}" : '');
	}


	/**
	 * @param  string
	 * @return bool
	 */
	public function hasExtension($name)
	{
		return in_array(strtolower($name), array_map('strtolower', $this->info->extensions), true);
	}
}
