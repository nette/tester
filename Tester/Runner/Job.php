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

	/** @var array  */
	private $options;

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
		$this->options = Tester\Helpers::parseDocComment(file_get_contents($this->file));
		$this->options['name'] =
			(isset($this->options[0]) ? preg_replace('#^TEST:\s*#i', '', $this->options[0]) . ' | ' : '')
			. implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $this->file), -3))
			. ($args ? " [{$args}]" : '');
	}


	/**
	 * Runs single test.
	 * @param  bool
	 * @return void
	 */
	public function run($blocking = TRUE)
	{
		$this->headers = $this->output = NULL;

		$cmd = $this->php->getCommandLine();
		if (isset($this->options['phpini'])) {
			foreach ((array) $this->options['phpini'] as $item) {
				$cmd .= ' -d ' . escapeshellarg(trim($item));
			}
		}
		$cmd .= ' ' . escapeshellarg($this->file) . ' ' . $this->args;

		$descriptors = array(
			array('pipe', 'r'),
			array('pipe', 'w'),
			array('pipe', 'w'),
		);

		putenv('NETTE_TESTER_COLORS=' . (int) Tester\Environment::$useColors);
		$this->proc = proc_open($cmd, $descriptors, $pipes, dirname($this->file), NULL, array('bypass_shell' => TRUE));
		list($stdin, $this->stdout, $stderr) = $pipes;
		fclose($stdin);
		stream_set_blocking($this->stdout, $blocking ? 1 : 0);
		fclose($stderr);
	}


	/**
	 * Checks if the test results are ready.
	 * @return bool
	 */
	public function isReady()
	{
		$this->output .= stream_get_contents($this->stdout);
		$status = proc_get_status($this->proc);
		if ($status['exitcode'] !== self::CODE_NONE) {
			$this->exitCode = $status['exitcode'];
		}
		return !$status['running'];
	}


	/**
	 * Collect results.
	 * @return void
	 */
	public function collect()
	{
		fclose($this->stdout);
		$code = proc_close($this->proc);
		if ($code !== self::CODE_NONE) {
			$this->exitCode = $code;
		}

		if ($this->php->isCgi() && count($tmp = explode("\r\n\r\n", $this->output, 2)) >= 2) {
			list($headers, $this->output) = $tmp;
			foreach (explode("\r\n", $headers) as $header) {
				$a = strpos($header, ':');
				if ($a !== FALSE) {
					$this->headers[trim(substr($header, 0, $a))] = (string) trim(substr($header, $a + 1));
				}
			}
		}
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
	 * Returns test name.
	 * @return string
	 */
	public function getName()
	{
		return $this->options['name'];
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
	 * Returns test options.
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
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
