<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester\Environment;


/**
 * Single test job.
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

	/** @var string[]  test arguments */
	private $args;

	/** @var string  test output */
	private $output;

	/** @var string[]  output headers */
	private $headers;

	/** @var PhpExecutable */
	private $php;

	/** @var resource */
	private $proc;

	/** @var resource */
	private $stdout;

	/** @var int */
	private $exitCode = self::CODE_NONE;


	/**
	 * @param  string  test file name
	 * @return void
	 */
	public function __construct($testFile, PhpExecutable $php, array $args = NULL)
	{
		$this->file = (string) $testFile;
		$this->php = $php;
		$this->args = (array) $args;
	}


	/**
	 * Runs single test.
	 * @param  bool  wait till process ends
	 * @return void
	 */
	public function run($blocking = TRUE)
	{
		putenv(Environment::RUNNER . '=1');
		putenv(Environment::COLORS . '=' . (int) Environment::$useColors);
		$this->proc = proc_open(
			$this->php->getCommandLine() . ' -n -d register_argc_argv=on ' . \Tester\Helpers::escapeArg($this->file) . ' ' . implode(' ', $this->args),
			array(
				array('pipe', 'r'),
				array('pipe', 'w'),
				array('pipe', 'w'),
			),
			$pipes,
			dirname($this->file),
			NULL,
			array('bypass_shell' => TRUE)
		);
		list($stdin, $this->stdout, $stderr) = $pipes;
		fclose($stdin);
		fclose($stderr);
		if ($blocking) {
			while ($this->isRunning()) {
				usleep(self::RUN_USLEEP); // stream_select() doesn't work with proc_open()
			}
		} else {
			stream_set_blocking($this->stdout, 0);
		}
	}


	/**
	 * Checks if the test is still running.
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
		$this->exitCode = $code === self::CODE_NONE ? $status['exitcode'] : $code;

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
	 * @return string[]
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
	 * @return string[]
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

}
