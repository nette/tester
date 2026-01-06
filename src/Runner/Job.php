<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester\Runner;

use Tester\Helpers;
use function count, is_array, is_resource;
use const DIRECTORY_SEPARATOR, PHP_OS_FAMILY, PHP_VERSION_ID;


/**
 * Single test job.
 */
class Job
{
	public const
		CodeNone = -1,
		CodeOk = 0,
		CodeSkip = 177,
		CodeFail = 178,
		CodeError = 255;

	/** waiting time between process activity check in microseconds */
	public const RunSleep = 10000;

	private Test $test;
	private PhpInterpreter $interpreter;

	/** @var string[]  environment variables for test */
	private array $envVars;

	/** @var resource|null */
	private $proc;

	/** @var resource|null */
	private $stdout;
	private ?string $stderrFile;
	private int $exitCode = self::CodeNone;

	/** @var string[]  output headers */
	private array $headers = [];
	private float $duration;


	/** @param ?array<string, string>  $envVars */
	public function __construct(Test $test, PhpInterpreter $interpreter, ?array $envVars = null)
	{
		if ($test->hasResult()) {
			throw new \LogicException("Test '{$test->getSignature()}' already has result '{$test->getResult()}'.");
		}

		$test->stdout = '';
		$test->stderr = '';

		$this->test = $test;
		$this->interpreter = $interpreter;
		$this->envVars = (array) $envVars;
	}


	public function setTempDirectory(?string $path): void
	{
		$this->stderrFile = $path === null
			? null
			: $path . DIRECTORY_SEPARATOR . 'Job.pid-' . getmypid() . '.' . uniqid() . '.stderr';
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
	 */
	public function run(bool $async = false): void
	{
		foreach ($this->envVars as $name => $value) {
			putenv("$name=$value");
		}

		$args = array_map(fn($arg) => is_array($arg) ? "--$arg[0]=$arg[1]" : $arg, $this->test->getArguments());
		$this->duration = -microtime(as_float: true);
		$this->proc = proc_open(
			$this->interpreter
				->withArguments(['-d register_argc_argv=on', $this->test->getFile(), ...$args])
				->getCommandLine(),
			[
				['pipe', 'r'],
				['pipe', 'w'],
				$this->stderrFile ? ['file', $this->stderrFile, 'w'] : ['pipe', 'w'],
			],
			$pipes,
			dirname($this->test->getFile()),
			null,
			['bypass_shell' => true],
		) ?: throw new \RuntimeException('Cannot start test process.');

		foreach (array_keys($this->envVars) as $name) {
			putenv($name);
		}

		[$stdin, $this->stdout] = $pipes;
		fclose($stdin);

		if (isset($pipes[2])) {
			fclose($pipes[2]);
		}

		if ($async) {
			stream_set_blocking($this->stdout, enable: false); // on Windows does not work with proc_open()
		} else {
			while ($this->isRunning()) {
				usleep(self::RunSleep);
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

		// PHP 8.5+ Windows: stream_select() works with pipes (PeekNamedPipe fix),
		if (PHP_OS_FAMILY === 'Windows' && PHP_VERSION_ID >= 80500) {
			$read = [$this->stdout];
			$w = $e = [];
			while (@stream_select($read, $w, $e, 0, 0) > 0) {
				$chunk = fread($this->stdout, 8192);
				if ($chunk === false || $chunk === '') {
					break;
				}
				$this->test->stdout .= $chunk;
				$read = [$this->stdout];
			}
		} else {
			// Linux/macOS: stream_get_contents() works without blocking
			// Windows < 8.5: blocks, but is necessary to prevent deadlock when output exceeds pipe buffer (~64KB)
			$this->test->stdout .= stream_get_contents($this->stdout);
		}

		$status = proc_get_status($this->proc);
		if ($status['running']) {
			return true;
		}

		$this->duration += microtime(as_float: true);

		stream_set_blocking($this->stdout, true);
		$this->test->stdout .= stream_get_contents($this->stdout);
		fclose($this->stdout);

		if ($this->stderrFile) {
			$this->test->stderr .= Helpers::readFile($this->stderrFile);
			unlink($this->stderrFile);
		}

		$code = proc_close($this->proc);
		$this->exitCode = $code === self::CodeNone
			? $status['exitcode']
			: $code;

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


	/**
	 * Returns process duration in seconds.
	 */
	public function getDuration(): ?float
	{
		return $this->duration > 0
			? $this->duration
			: null;
	}


	/**
	 * Waits for activity on any of the running jobs.
	 * @param  self[]  $jobs
	 */
	public static function waitForActivity(array $jobs): void
	{
		if (PHP_OS_FAMILY === 'Windows' && PHP_VERSION_ID < 80500) {
			usleep(self::RunSleep);
			return;
		}

		$streams = array_filter(array_map(fn($job) => $job->stdout, $jobs));
		if ($streams) {
			$w = $e = [];
			@stream_select($streams, $w, $e, 0, self::RunSleep);
		}
	}
}
