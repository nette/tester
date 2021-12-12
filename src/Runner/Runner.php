<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester\Runner;

use Tester\Environment;


/**
 * Test runner.
 */
class Runner
{
	/** @var string[]  paths to test files/directories */
	public $paths = [];

	/** @var string[] */
	public $ignoreDirs = ['vendor'];

	/** @var int  run in parallel threads */
	public $threadCount = 1;

	/** @var TestHandler */
	public $testHandler;

	/** @var OutputHandler[] */
	public $outputHandlers = [];

	/** @var bool */
	public $stopOnFail = false;

	/** @var PhpInterpreter */
	private $interpreter;

	/** @var array */
	private $envVars = [];

	/** @var Job[] */
	private $jobs;

	/** @var bool */
	private $interrupted = false;

	/** @var string|null */
	private $tempDir;

	/** @var bool */
	private $result;

	/** @var array */
	private $lastResults = [];


	public function __construct(PhpInterpreter $interpreter)
	{
		$this->interpreter = $interpreter;
		$this->testHandler = new TestHandler($this);
	}


	public function setEnvironmentVariable(string $name, string $value): void
	{
		$this->envVars[$name] = $value;
	}


	public function getEnvironmentVariables(): array
	{
		return $this->envVars;
	}


	public function addPhpIniOption(string $name, ?string $value = null): void
	{
		$this->interpreter = $this->interpreter->withPhpIniOption($name, $value);
	}


	public function setTempDirectory(?string $path): void
	{
		if ($path !== null) {
			if (!is_dir($path) || !is_writable($path)) {
				throw new \RuntimeException("Path '$path' is not a writable directory.");
			}

			$path = realpath($path) . DIRECTORY_SEPARATOR . 'Tester';
			if (!is_dir($path) && @mkdir($path) === false && !is_dir($path)) {  // @ - directory may exist
				throw new \RuntimeException("Cannot create '$path' directory.");
			}
		}

		$this->tempDir = $path;
		$this->testHandler->setTempDirectory($path);
	}


	/**
	 * Runs all tests.
	 */
	public function run(): bool
	{
		$this->result = true;
		$this->interrupted = false;

		foreach ($this->outputHandlers as $handler) {
			$handler->begin();
		}

		$this->jobs = $running = [];
		foreach ($this->paths as $path) {
			$this->findTests($path);
		}

		if ($this->tempDir) {
			usort($this->jobs, function (Job $a, Job $b): int {
				return $this->getLastResult($a->getTest()) - $this->getLastResult($b->getTest());
			});
		}

		$threads = range(1, $this->threadCount);

		$async = $this->threadCount > 1 && count($this->jobs) > 1;

		try {
			while (($this->jobs || $running) && !$this->interrupted) {
				while ($threads && $this->jobs) {
					$running[] = $job = array_shift($this->jobs);
					$job->setEnvironmentVariable(Environment::THREAD, (string) array_shift($threads));
					$job->run($async ? $job::RUN_ASYNC : 0);
				}

				if ($async) {
					usleep(Job::RUN_USLEEP); // stream_select() doesn't work with proc_open()
				}

				foreach ($running as $key => $job) {
					if ($this->interrupted) {
						break 2;
					}

					if (!$job->isRunning()) {
						$threads[] = $job->getEnvironmentVariable(Environment::THREAD);
						$this->testHandler->assess($job);
						unset($running[$key]);
					}
				}
			}
		} finally {
			foreach ($this->outputHandlers as $handler) {
				$handler->end();
			}
		}

		return $this->result;
	}


	private function findTests(string $path): void
	{
		if (strpbrk($path, '*?') === false && !file_exists($path)) {
			throw new \InvalidArgumentException("File or directory '$path' not found.");
		}

		if (is_dir($path)) {
			foreach (glob(str_replace('[', '[[]', $path) . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
				if (in_array(basename($dir), $this->ignoreDirs, true)) {
					continue;
				}

				$this->findTests($dir);
			}

			$this->findTests($path . '/*.phpt');
			$this->findTests($path . '/*Test.php');

		} else {
			foreach (glob(str_replace('[', '[[]', $path)) ?: [] as $file) {
				if (is_file($file)) {
					$this->testHandler->initiate(realpath($file));
				}
			}
		}
	}


	/**
	 * Appends new job to queue.
	 */
	public function addJob(Job $job): void
	{
		$this->jobs[] = $job;
	}


	public function prepareTest(Test $test): void
	{
		foreach ($this->outputHandlers as $handler) {
			$handler->prepare($test);
		}
	}


	/**
	 * Writes to output handlers.
	 */
	public function finishTest(Test $test): void
	{
		$this->result = $this->result && ($test->getResult() !== Test::FAILED);

		foreach ($this->outputHandlers as $handler) {
			$handler->finish($test);
		}

		if ($this->tempDir) {
			$lastResult = &$this->lastResults[$test->getSignature()];
			if ($lastResult !== $test->getResult()) {
				file_put_contents($this->getLastResultFilename($test), $lastResult = $test->getResult());
			}
		}

		if ($this->stopOnFail && $test->getResult() === Test::FAILED) {
			$this->interrupted = true;
		}
	}


	public function getInterpreter(): PhpInterpreter
	{
		return $this->interpreter;
	}


	private function getLastResult(Test $test): int
	{
		$signature = $test->getSignature();
		if (isset($this->lastResults[$signature])) {
			return $this->lastResults[$signature];
		}

		$file = $this->getLastResultFilename($test);
		if (is_file($file)) {
			return $this->lastResults[$signature] = (int) file_get_contents($file);
		}

		return $this->lastResults[$signature] = Test::PREPARED;
	}


	private function getLastResultFilename(Test $test): string
	{
		return $this->tempDir
			. DIRECTORY_SEPARATOR
			. pathinfo($test->getFile(), PATHINFO_FILENAME)
			. '.'
			. substr(md5($test->getSignature()), 0, 5)
			. '.result';
	}
}
