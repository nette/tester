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
 * Test runner.
 *
 * @author     David Grudl
 */
class Runner
{
	const
		PASSED = 1,
		SKIPPED = 2,
		FAILED = 3;

	/** waiting time between runs in microseconds */
	const RUN_USLEEP = 10000;

	/** @var array  paths to test files/directories */
	public $paths = array();

	/** @var int  run jobs in parallel */
	public $jobCount = 1;

	/** @var TestHandler */
	public $testHandler;

	/** @var OutputHandler[] */
	public $outputHandlers = array();

	/** @var PhpExecutable */
	private $php;

	/** @var Job[] */
	private $jobs;

	/** @var array */
	private $results;


	public function __construct(PhpExecutable $php)
	{
		$this->php = $php;
		$this->testHandler = new TestHandler($this);
	}


	/**
	 * Runs all tests.
	 * @return void
	 */
	public function run()
	{
		foreach ($this->outputHandlers as $hander) {
			$hander->begin();
		}

		$this->results = array(self::PASSED => 0, self::SKIPPED => 0, self::FAILED => 0);
		$this->jobs = $running = array();
		foreach ($this->paths as $path) {
			$this->findTests($path);
		}

		while ($this->jobs || $running) {
			for ($i = count($running); $this->jobs && $i < $this->jobCount; $i++) {
				$running[] = $job = array_shift($this->jobs);
				$job->run($this->jobCount <= 1 || (count($running) + count($this->jobs) <= 1));
			}

			if (count($running) > 1) {
				usleep(self::RUN_USLEEP); // stream_select() doesn't work with proc_open()
			}

			foreach ($running as $key => $job) {
				if (!$job->isRunning()) {
					$this->testHandler->assess($job);
					unset($running[$key]);
				}
			}
		}

		foreach ($this->outputHandlers as $hander) {
			$hander->end();
		}
		return !$this->results[self::FAILED];
	}


	/**
	 * @return void
	 */
	private function findTests($path)
	{
		if (is_dir($path)) {
			foreach (glob("$path/*", GLOB_ONLYDIR) as $dir) {
				$this->findTests($dir);
			}
			$path .= '/*.phpt';
		}
		foreach (glob($path) as $file) {
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
	 * Writes to output handlers.
	 * @return void
	 */
	public function writeResult($testName, $result, $message = NULL)
	{
		$this->results[$result]++;
		foreach ($this->outputHandlers as $hander) {
			$hander->result($testName, $result, $message);
		}
	}


	/**
	 * @return PhpExecutable
	 */
	public function getPhp()
	{
		return $this->php;
	}


	/**
	 * @return array
	 */
	public function getResults()
	{
		return $this->results;
	}

}
