<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester\Runner;

use Tester\Helpers;


/**
 * Single test job.
 */
class Job
{
	public const
		CODE_NONE = -1,
		CODE_OK = 0,
		CODE_SKIP = 177,
		CODE_FAIL = 178,
		CODE_ERROR = 255;

	/** waiting time between process activity check in microseconds */
	public const RUN_USLEEP = 10000;

	public const
		RUN_ASYNC = 1,
		RUN_COLLECT_ERRORS = 2;

	/** @var Test */
	private $test;

	/** @var PhpInterpreter */
	private $interpreter;

	/** @var string[]  environment variables for test */
	private $envVars;

	/** @var resource|null */
	private $proc;

	/** @var resource|null */
	private $stdout;

	/** @var resource|null */
	private $stderr;

	/** @var int */
	private $exitCode = self::CODE_NONE;

	/** @var string[]  output headers */
	private $headers = [];


	public function __construct(Test $test, PhpInterpreter $interpreter, array $envVars = null)
	{
		if ($test->getResult() !== Test::PREPARED) {
			throw new \LogicException("Test '{$test->getSignature()}' already has result '{$test->getResult()}'.");
		}

		$test->stdout = '';
		$test->stderr = '';

		$this->test = $test;
		$this->interpreter = $interpreter;
		$this->envVars = (array) $envVars;
	}


	public function setEnvironmentVariable(string $name, string $value): void
	{
		$this->envVars[$name] = $value;
	}


	public function getEnvironmentVariable(string $name): string
	{
		return $this->envVars[$name];
	}


	/**
	 * Runs single test.
	 * @param  int  $flags  self::RUN_ASYNC | self::RUN_COLLECT_ERRORS
	 */
	public function run(int $flags = 0): void
	{
		foreach ($this->envVars as $name => $value) {
			putenv("$name=$value");
		}

		$args = [];
		foreach ($this->test->getArguments() as $value) {
			if (is_array($value)) {
				$args[] = Helpers::escapeArg("--$value[0]=$value[1]");
			} else {
				$args[] = Helpers::escapeArg($value);
			}
		}

		$this->proc = proc_open(
			$this->interpreter->getCommandLine()
			. ' -d register_argc_argv=on ' . Helpers::escapeArg($this->test->getFile()) . ' ' . implode(' ', $args),
			[
				['pipe', 'r'],
				['pipe', 'w'],
				['pipe', 'w'],
			],
			$pipes,
			dirname($this->test->getFile()),
			null,
			['bypass_shell' => true]
		);

		foreach (array_keys($this->envVars) as $name) {
			putenv($name);
		}

		[$stdin, $this->stdout, $stderr] = $pipes;
		fclose($stdin);
		if ($flags & self::RUN_COLLECT_ERRORS) {
			$this->stderr = $stderr;
		} else {
			fclose($stderr);
		}

		if ($flags & self::RUN_ASYNC) {
			stream_set_blocking($this->stdout, false); // on Windows does not work with proc_open()
			if ($this->stderr) {
				stream_set_blocking($this->stderr, false);
			}
		} else {
			while ($this->isRunning()) {
				usleep(self::RUN_USLEEP); // stream_select() doesn't work with proc_open()
			}
		}
	}


	/**
	 * Checks if the test is still running.
	 */
	public function isRunning(): bool
	{
		if (!is_resource($this->stdout)) {
			return false;
		}
		$this->test->stdout .= stream_get_contents($this->stdout);
		if ($this->stderr) {
			$this->test->stderr .= stream_get_contents($this->stderr);
		}

		$status = proc_get_status($this->proc);
		if ($status['running']) {
			return true;
		}

		fclose($this->stdout);
		if ($this->stderr) {
			fclose($this->stderr);
		}
		$code = proc_close($this->proc);
		$this->exitCode = $code === self::CODE_NONE ? $status['exitcode'] : $code;

		if ($this->interpreter->isCgi() && count($tmp = explode("\r\n\r\n", $this->test->stdout, 2)) >= 2) {
			[$headers, $this->test->stdout] = $tmp;
			foreach (explode("\r\n", $headers) as $header) {
				$pos = strpos($header, ':');
				if ($pos !== false) {
					$this->headers[trim(substr($header, 0, $pos))] = trim(substr($header, $pos + 1));
				}
			}
		}
		return false;
	}


	public function getTest(): Test
	{
		return $this->test;
	}


	/**
	 * Returns exit code.
	 */
	public function getExitCode(): int
	{
		return $this->exitCode;
	}


	/**
	 * Returns output headers.
	 * @return string[]
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}
}
