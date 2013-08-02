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

use Tester;


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

	/** @var string  test file */
	private $file;

	/** @var string  test arguments */
	private $args;

	/** @var string  test output */
	private $output;

	/** @var string  output headers in raw format */
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
	public function __construct($testFile, PhpExecutable $php, $args = NULL)
	{
		$this->file = (string) $testFile;
		$this->php = $php;
		$this->args = $args;
	}


	/**
	 * Runs single test.
	 * @param  bool
	 * @return void
	 */
	public function run($blocking = TRUE)
	{
		putenv('NETTE_TESTER_RUNNER=1');
		putenv('NETTE_TESTER_COLORS=' . (int) Tester\Environment::$useColors);
		$this->proc = proc_open(
			$this->php->getCommandLine() . ' ' . escapeshellarg($this->file) . ' ' . $this->args,
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
		stream_set_blocking($this->stdout, $blocking ? 1 : 0);
		fclose($stderr);
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
	 * @return string
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
	 * @return string
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

}
