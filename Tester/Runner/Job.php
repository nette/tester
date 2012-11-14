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
		CODE_SKIP = 253,
		CODE_ERROR = 255,
		CODE_FAIL = 254;

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
	 * @param  string  command line
	 * @return void
	 */
	public function __construct($testFile, $args = NULL, PhpExecutable $php)
	{
		$this->file = (string) $testFile;
		$this->args = $args;
		$this->options = self::parseOptions($this->file);
		$this->php = $php;
	}



	/**
	 * Runs single test.
	 * @param  bool
	 * @return Job  provides a fluent interface
	 */
	public function run($blocking = true)
	{
		// pre-skip?
		if (isset($this->options['skip'])) {
			$message = $this->options['skip'] ? $this->options['skip'] : 'No message.';
			throw new JobException($message, JobException::SKIPPED);

		} elseif (isset($this->options['phpversion'])) {
			$operator = '>=';
			if (preg_match('#^(<=|le|<|lt|==|=|eq|!=|<>|ne|>=|ge|>|gt)#', $this->options['phpversion'], $matches)) {
				$this->options['phpversion'] = trim(substr($this->options['phpversion'], strlen($matches[1])));
				$operator = $matches[1];
			}
			if (version_compare($this->options['phpversion'], $this->php->getVersion(), $operator)) {
				throw new JobException("Requires PHP $operator {$this->options['phpversion']}.", JobException::SKIPPED);
			}
		}

		$this->execute($blocking);
		return $this;
	}



	/**
	 * Execute test.
	 * @param  bool
	 * @return void
	 */
	private function execute($blocking)
	{
		$this->headers = $this->output = NULL;

		$cmd = $this->php->getCommandLine();
		if (isset($this->options['phpini'])) {
			foreach (explode(';', $this->options['phpini']) as $item) {
				$cmd .= ' -d ' . escapeshellarg(trim($item));
			}
		}
		$cmd .= ' ' . escapeshellarg($this->file) . ' ' . $this->args;

		$descriptors = array(
			array('pipe', 'r'),
			array('pipe', 'w'),
			array('pipe', 'w'),
		);

		$this->proc = proc_open($cmd, $descriptors, $pipes, dirname($this->file), null, array('bypass_shell' => true));
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
		$this->output .= stream_get_contents($this->stdout);
		fclose($this->stdout);
		$res = proc_close($this->proc);
		if ($res === self::CODE_NONE) {
			$res = $this->exitCode;
		}

		if ($this->php->isCgi() && count($tmp = explode("\r\n\r\n", $this->output, 2)) >= 2) {
			list($headers, $this->output) = $tmp;
		} else {
			$headers = '';
		}

		$this->headers = array();
		foreach (explode("\r\n", $headers) as $header) {
			$a = strpos($header, ':');
			if ($a !== FALSE) {
				$this->headers[trim(substr($header, 0, $a))] = (string) trim(substr($header, $a + 1));
			}
		}

		if ($res === self::CODE_SKIP) {
			throw new JobException($this->output, JobException::SKIPPED);

		} elseif ($res !== self::CODE_OK) {
			throw new JobException($this->output ?: 'Fatal error');
		}

		// HTTP code check
		if (isset($this->options['assertcode'])) {
			$code = isset($this->headers['Status']) ? (int) $this->headers['Status'] : 200;
			if ($code !== (int) $this->options['assertcode']) {
				throw new JobException('Expected HTTP code ' . $this->options['assertcode'] . ' is not same as actual code ' . $code);
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
	 * Returns test args.
	 * @return string
	 */
	public function getArguments()
	{
		return $this->args;
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



	/********************* helpers ****************d*g**/



	/**
	 * Parse phpDoc.
	 * @param  string  file
	 * @return array
	 */
	public static function parseOptions($testFile)
	{
		$content = file_get_contents($testFile);
		$options = array();
		$phpDoc = preg_match('#^/\*\*(.*?)\*/#ms', $content, $matches) ? trim($matches[1]) : '';
		preg_match_all('#^\s*\*\s*@(\S+)(.*)#mi', $phpDoc, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$options[strtolower($match[1])] = isset($match[2]) ? trim($match[2]) : TRUE;
		}
		$options['name'] = preg_match('#^\s*\*\s*TEST:(.*)#mi', $phpDoc, $matches) ? trim($matches[1]) : $testFile;
		return $options;
	}

}



/**
 * Single test exception.
 *
 * @author     David Grudl
 */
class JobException extends \Exception
{
	const SKIPPED = 1;

}
