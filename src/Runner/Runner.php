<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester;


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
	public $paths = array();

	/** @var int  run in parallel threads */
	public $threadCount = 1;

	/** @var TestHandler */
	public $testHandler;

	/** @var OutputHandler[] */
	public $outputHandlers = array();

	/** @var bool */
	public $stopOnFail = FALSE;

	/** @var PhpInterpreter */
	private $interpreter;

	/** @var Job[] */
	private $jobs;

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
	 * Runs all tests.
	 * @return bool
	 */
	public function run()
	{
		$this->interrupted = FALSE;

		foreach ($this->outputHandlers as $handler) {
			$handler->begin();
		}

		$this->results = array(self::PASSED => 0, self::SKIPPED => 0, self::FAILED => 0);
		$this->jobs = $running = array();
		foreach ($this->paths as $path) {
			$this->findTests($path);
		}
		$this->jobCount = count($this->jobs) + array_sum($this->results);

		$this->installInterruptHandler();
		while (($this->jobs || $running) && !$this->isInterrupted()) {
			for ($i = count($running); $this->jobs && $i < $this->threadCount; $i++) {
				$running[] = $job = array_shift($this->jobs);
				$async = $this->threadCount > 1 && (count($running) + count($this->jobs) > 1);
				$job->run($async ? $job::RUN_ASYNC : NULL);
			}

			if (count($running) > 1) {
				usleep(Job::RUN_USLEEP); // stream_select() doesn't work with proc_open()
			}

			foreach ($running as $key => $job) {
				if ($this->isInterrupted()) {
					break 2;
				}

				if (!$job->isRunning()) {
					$this->testHandler->assess($job);
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
			foreach (glob(str_replace('[', '[[]', $path) . '/*', GLOB_ONLYDIR) ?: array() as $dir) {
				$this->findTests($dir);
			}
			$path .= '/*.' . self::TEST_FILE_EXTENSION;
		}
		foreach (glob(str_replace('[', '[[]', $path)) ?: array() as $file) {
			if (is_file($file)) {
				$this->testHandler->initiate(realpath($file));
			}
		}
	}


	/**
	 * Appends new job to queue.
	 * @return void
	 */
	public function addJob(Job $job)
	{
		$this->jobs[] = $job;
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
	public function writeResult($testName, $result, $message = NULL)
	{
		$this->results[$result]++;
		foreach ($this->outputHandlers as $handler) {
			$handler->result($testName, $result, $message);
		}

		if ($this->stopOnFail && $result === self::FAILED) {
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
			$interrupted = & $this->interrupted;
			pcntl_signal(SIGINT, function () use (& $interrupted) {
				pcntl_signal(SIGINT, SIG_DFL);
				$interrupted = TRUE;
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
