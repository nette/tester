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
	/** @var string[]  paths to test files/directories */
	public $paths = [];

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
	private $interrupted;

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
	 * @param  string|null
	 */
	public function setTempDirectory($path)
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
	}


	/**
	 * Runs all tests.
	 * @return bool
	 */
	public function run()
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
			usort($this->jobs, function (Job $a, Job $b) {
				return $this->getLastResult($a->getTest()) - $this->getLastResult($b->getTest());
			});
		}

		$threads = range(1, $this->threadCount);

		$this->installInterruptHandler();
		while (($this->jobs || $running) && !$this->isInterrupted()) {
			while ($threads && $this->jobs) {
				$running[] = $job = array_shift($this->jobs);
				$async = $this->threadCount > 1 && (count($running) + count($this->jobs) > 1);
				$job->setEnvironmentVariable(Environment::THREAD, array_shift($threads));
				$job->run($async ? $job::RUN_ASYNC : 0);
			}

			if (count($running) > 1) {
				usleep(Job::RUN_USLEEP); // stream_select() doesn't work with proc_open()
			}

			foreach ($running as $key => $job) {
				if ($this->isInterrupted()) {
					break 2;
				}

				if (!$job->isRunning()) {
					$threads[] = $job->getEnvironmentVariable(Environment::THREAD);
					$this->testHandler->assess($job);
					unset($running[$key]);
				}
			}
		}
		$this->removeInterruptHandler();

		foreach ($this->outputHandlers as $handler) {
			$handler->end();
		}

		return $this->result;
	}


	/**
	 * @return void
	 */
	private function findTests($path)
	{
		if (strpbrk($path, '*?') === false && !file_exists($path)) {
			throw new \InvalidArgumentException("File or directory '$path' not found.");
		}

		if (is_dir($path)) {
			foreach (glob(str_replace('[', '[[]', $path) . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
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
	 * @return void
	 */
	public function addJob(Job $job)
	{
		$this->jobs[] = $job;
	}


	/**
	 * @return void
	 */
	public function prepareTest(Test $test)
	{
		foreach ($this->outputHandlers as $handler) {
			$handler->prepare($test);
		}
	}


	/**
	 * Writes to output handlers.
	 * @return void
	 */
	public function finishTest(Test $test)
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


	/**
	 * @return PhpInterpreter
	 */
	public function getInterpreter()
	{
		return $this->interpreter;
	}


	/**
	 * @return void
	 */
	private function installInterruptHandler()
	{
		if (extension_loaded('pcntl')) {
			pcntl_signal(SIGINT, function () {
				pcntl_signal(SIGINT, SIG_DFL);
				$this->interrupted = true;
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


	/**
	 * @return string
	 */
	private function getLastResult(Test $test)
	{
		$signature = $test->getSignature();
		if (isset($this->lastResults[$signature])) {
			return $this->lastResults[$signature];
		}

		$file = $this->getLastResultFilename($test);
		if (is_file($file)) {
			return $this->lastResults[$signature] = file_get_contents($file);
		}

		return $this->lastResults[$signature] = Test::PREPARED;
	}


	/**
	 * @return string
	 */
	private function getLastResultFilename(Test $test)
	{
		return $this->tempDir
			. DIRECTORY_SEPARATOR
			. pathinfo($test->getFile(), PATHINFO_FILENAME)
			. '.'
			. substr(md5($test->getSignature()), 0, 5)
			. '.result';
	}
}
