<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester\Environment;


/**
 * Test runner.
 */
class Runner
{
	const
		PASSED = 1,
		SKIPPED = 2,
		FAILED = 3;

	const TEST_FILE_EXTENSION = 'phpt';

	/** @var string[]  paths to test files/directories */
	public $paths = [];

	/** @var int  run in parallel threads */
	public $threadCount = 1;

	/** @var TestHandler */
	public $testHandler;

	/** @var OutputHandler[] */
	public $outputHandlers = [];

	/** @var bool */
	public $stopOnFail = FALSE;

	/** @var PhpInterpreter */
	private $interpreter;

	/** @var array */
	private $envVars = [];

	/** @var TestInstance[] */
	private $testInstances;

	/** @var int */
	private $jobCount;

	/** @var array */
	private $results;

	/** @var bool */
	private $interrupted;


	public function __construct(PhpInterpreter $interpreter)
	{
		$this->interpreter = $interpreter;
		$this->testHandler = new TestHandler($this);
	}


	/**
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function setEnvironmentVariable($name, $value)
	{
		$this->envVars[$name] = $value;
	}


	/**
	 * @return array
	 */
	public function getEnvironmentVariables()
	{
		return $this->envVars;
	}


	/**
	 * Runs all tests.
	 * @return bool
	 */
	public function run()
	{
		$this->interrupted = FALSE;

		$this->results = [self::PASSED => 0, self::SKIPPED => 0, self::FAILED => 0];
		$this->testInstances = $running = [];
		foreach ($this->paths as $path) {
			$this->findTests($path);
		}

		$this->jobCount = count($this->testInstances);

		foreach ($this->outputHandlers as $handler) {
			$handler->begin($this->testInstances);
		}

		$threads = range(1, $this->threadCount);

		$this->installInterruptHandler();
		while (($this->testInstances || $running) && !$this->isInterrupted()) {
			while ($threads && $this->testInstances) {
				$instance = array_shift($this->testInstances);
				if ($job = $instance->getJob()) {
					$running[] = $instance;
					$async = $this->threadCount > 1 && (count($running) + count($this->testInstances) > 1);
					$job->setEnvironmentVariable(Environment::THREAD, array_shift($threads));
					$job->run($async ? $job::RUN_ASYNC : NULL);

				} else {
					$this->writeResult($instance);
				}
			}

			if (count($running) > 1) {
				usleep(Job::RUN_USLEEP); // stream_select() doesn't work with proc_open()
			}

			foreach ($running as $key => $instance) {
				if ($this->isInterrupted()) {
					break 2;
				}

				$job = $instance->getJob();
				if (!$job->isRunning()) {
					$threads[] = $job->getEnvironmentVariable(Environment::THREAD);
					$this->testHandler->assess($instance);
					unset($running[$key]);
				}
			}
		}
		$this->removeInterruptHandler();

		foreach ($this->outputHandlers as $handler) {
			$handler->end();
		}
		return !$this->results[self::FAILED];
	}


	/**
	 * @return void
	 */
	private function findTests($path)
	{
		if (strpbrk($path, '*?') === FALSE && !file_exists($path)) {
			throw new \InvalidArgumentException("File or directory '$path' not found.");
		}

		if (is_dir($path)) {
			foreach (glob(str_replace('[', '[[]', $path) . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
				$this->findTests($dir);
			}
			$path .= '/*.' . self::TEST_FILE_EXTENSION;
		}
		foreach (glob(str_replace('[', '[[]', $path)) ?: [] as $file) {
			if (is_file($file)) {
				$this->testHandler->initiate(realpath($file));
			}
		}
	}


	/**
	 * Appends new test instance to queue.
	 * @return void
	 */
	public function addTestInstance(TestInstance $testInstance)
	{
		$this->testInstances[] = $testInstance;
	}


	/**
	 * Get count of jobs.
	 * @return int
	 */
	public function getJobCount()
	{
		return $this->jobCount;
	}


	/**
	 * Writes to output handlers.
	 * @return void
	 */
	public function writeResult(TestInstance $testInstance)
	{
		$this->results[$testInstance->getResult()]++;
		foreach ($this->outputHandlers as $handler) {
			$handler->result($testInstance);
		}

		if ($this->stopOnFail && $testInstance->getResult() === self::FAILED) {
			$this->interrupted = TRUE;
		}
	}


	/**
	 * @return PhpInterpreter
	 */
	public function getInterpreter()
	{
		return $this->interpreter;
	}


	/**
	 * @return array
	 */
	public function getResults()
	{
		return $this->results;
	}


	/**
	 * @return void
	 */
	private function installInterruptHandler()
	{
		if (extension_loaded('pcntl')) {
			pcntl_signal(SIGINT, function () {
				pcntl_signal(SIGINT, SIG_DFL);
				$this->interrupted = TRUE;
			});
		}
	}


	/**
	 * @return void
	 */
	private function removeInterruptHandler()
	{
		if (extension_loaded('pcntl')) {
			pcntl_signal(SIGINT, SIG_DFL);
		}
	}


	/**
	 * @return bool
	 */
	private function isInterrupted()
	{
		if (extension_loaded('pcntl')) {
			pcntl_signal_dispatch();
		}

		return $this->interrupted;
	}

}
