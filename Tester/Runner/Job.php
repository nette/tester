<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester\Environment;


/**
 * Single test job.
 *
 * @author     David Grudl
 */
class Job
{
	const
		CODE_NONE = -1,
		CODE_OK = 0,
		CODE_SKIP = 177,
		CODE_FAIL = 178,
		CODE_ERROR = 255;

	/** waiting time between process activity check in microseconds */
	const RUN_USLEEP = 10000;

	/** @var string  test file */
	private $file;

	/** @var array  test arguments */
	private $args;

	/** @var string  test output */
	private $output;

	/** @var string  output headers in raw format */
	private $headers;

	/** @var IPhpInterpreter */
	private $php;

	/** @var resource */
	private $proc;

	/** @var resource */
	private $stdout;

	/** @var int */
	private $exitCode = self::CODE_NONE;

	/** @var bool */
	private $finished;


	/**
	 * @param  string  test file name
	 * @return void
	 */
	public function __construct($testFile, IPhpInterpreter $php, array $args = array())
	{
		$this->file = (string) $testFile;
		$this->php = clone $php; /** @todo Clone? */
		$this->args = $args;
	}


	/**
	 * Runs single test.
	 * @param  bool  wait till process ends
	 * @return void
	 */
	public function run($blocking = TRUE)
	{
		$iniValues = array(
			'register_argc_argv' => 'on',
		);

		$envVars = array(
			Environment::RUNNER => 1,
			Environment::COLORS => (int) Environment::$useColors,
		);

		$this->finished = FALSE;
		$this->php->run($this->file, $this->args, $iniValues, $envVars);

		if ($blocking) {
			while ($this->isRunning()) {
				usleep(self::RUN_USLEEP);
			}
		}
	}


	/**
	 * Checks if the test is still running.
	 * @return bool
	 */
	public function isRunning()
	{
		if ($this->finished) {
			return FALSE;
		}

		if ($this->php->isRunning()) {
			return TRUE;
		}

		list($this->exitCode, $this->output) = $this->php->getResult();

		if ($this->php->isCgi() && count($tmp = explode("\r\n\r\n", $this->output, 2)) >= 2) {
			list($headers, $this->output) = $tmp;
			foreach (explode("\r\n", $headers) as $header) {
				$a = strpos($header, ':');
				if ($a !== FALSE) {
					$this->headers[trim(substr($header, 0, $a))] = (string) trim(substr($header, $a + 1));
				}
			}
		}

		return FALSE;
	}


	/**
	 * Returns test file path.
	 * @return string
	 */
	public function getFile()
	{
		return $this->file;
	}


	/**
	 * Returns script arguments.
	 * @return array
	 */
	public function getArguments()
	{
		return $this->args;
	}


	/**
	 * Returns exit code.
	 * @return int
	 */
	public function getExitCode()
	{
		return $this->exitCode;
	}


	/**
	 * Returns test output.
	 * @return string
	 */
	public function getOutput()
	{
		return $this->output;
	}


	/**
	 * Returns output headers.
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

}
