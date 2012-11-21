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
 * Test runner.
 *
 * @author     David Grudl
 */
class Runner
{
	/** waiting time between runs in microseconds */
	const RUN_USLEEP = 10000;

	/** @var array  paths to test files/directories */
	public $paths = array();

	/** @var bool  display skipped tests information? */
	public $displaySkipped = FALSE;

	/** @var int  run jobs in parallel */
	public $jobs = 1;

	/** @var resource */
	private $logFile;

	/** @var PhpExecutable */
	private $php;

	/** @var array */
	private $passed;

	/** @var array */
	private $failed;

	/** @var array */
	private $skipped;



	public function __construct(PhpExecutable $php, $logFile = NULL)
	{
		$this->php = $php;
		$this->logFile = $logFile ? fopen($logFile, 'w') : NULL;
	}



	/**
	 * Runs all tests.
	 * @return void
	 */
	public function run()
	{
		echo $this->log('PHP ' . $this->php->getVersion() . ' | ' . $this->php->getCommandLine() . "\n");

		$this->passed = $this->failed = $this->skipped = array();
		$tests = $this->findTests();
		if (!$tests && !$this->skipped) {
			echo $this->log("No tests found\n");
			return;
		}
		echo str_repeat('s', count($this->skipped));

		$this->runTests($tests);

		if ($this->displaySkipped) {
			echo "\n", implode($this->skipped);
		}

		if ($this->failed) {
			echo "\n", implode($this->failed);
			echo $this->log("\nFAILURES! (" . count($tests) . ' tests, ' . count($this->failed) . ' failures, ' . count($this->skipped) . ' skipped)');
			return FALSE;

		} else {
			echo $this->log("\n\nOK (" . count($tests) . ' tests, ' . count($this->skipped) . ' skipped)');
			return TRUE;
		}
	}



	/**
	 * @return void
	 */
	private function runTests(array & $tests)
	{
		$running = array();
		while ($tests || $running) {
			for ($i = count($running); $tests && $i < $this->jobs; $i++) {
				$job = array_shift($tests);
				try {
					$parallel = ($this->jobs > 1) && (count($running) + count($tests) > 1);
					$running[] = $job->run(!$parallel);
				} catch (JobException $e) {
					echo 's';
					$this->skipped[] = $this->log($this->format('Skipped', $job, $e->getMessage()));
				}
			}

			if (count($running) > 1) {
				usleep(self::RUN_USLEEP); // stream_select() doesn't work with proc_open()
			}

			foreach ($running as $key => $job) {
				if (!$job->isReady()) {
					continue;
				}
				try {
					$job->collect();
					echo '.';
					$this->passed[] = array($job->getName(), $job->getFile());

				} catch (JobException $e) {
					if ($e->getCode() === JobException::SKIPPED) {
						echo 's';
						$this->skipped[] = $this->log($this->format('Skipped', $job, $e->getMessage()));

					} else {
						echo 'F';
						$this->failed[] = $this->log($this->format('FAILED', $job, $e->getMessage()));
					}
				}
				unset($running[$key]);
			}
		}
	}



	/**
	 * @return Job[]
	 */
	private function findTests()
	{
		$tests = array();
		foreach ($this->paths as $path) {
			$files = is_file($path) ? array($path) : new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
			foreach ($files as $file) {
				$file = (string) $file;
				if (pathinfo($file, PATHINFO_EXTENSION) === 'phpt') {
					$this->processFile($file, $tests);
				}
			}
		}
		return $tests;
	}



	/**
	 * @return void
	 */
	private function processFile($file, & $tests)
	{
		$job = new Job($file, $this->php);
		$options = $job->getOptions();
		$range = array(NULL);

		if (isset($options['skip'])) {
			$this->skipped[] = $this->log($this->format('Skipped', $job, $options['skip']));
			return;

		} elseif (isset($options['phpversion'])) {
			$operator = '>=';
			if (preg_match('#^(<=|le|<|lt|==|=|eq|!=|<>|ne|>=|ge|>|gt)#', $options['phpversion'], $matches)) {
				$options['phpversion'] = trim(substr($options['phpversion'], strlen($matches[1])));
				$operator = $matches[1];
			}
			if (version_compare($options['phpversion'], $this->php->getVersion(), $operator)) {
				$this->skipped[] = $this->log($this->format('Skipped', $job, "Requires PHP $operator {$options['phpversion']}."));
				return;
			}
		}

		if (!empty($options['multiple'])) {
			if (is_numeric($options['multiple'])) {
				$range = range(0, $options['multiple'] - 1);

			} elseif (!is_file($multiFile = dirname($file) . '/' . $options['multiple'])) {
				throw new \Exception("Missing @multiple configuration file '$multiFile'.");

			} elseif (($multiple = parse_ini_file($multiFile, TRUE)) === FALSE) {
				throw new \Exception("Cannot parse @multiple configuration file '$multiFile'.");

			} else {
				$range = array_keys($multiple);
			}

		} elseif (isset($options['testcase']) && preg_match_all('#\sfunction\s+(test\w+)\(#', file_get_contents($file), $matches)) {
			$range = $matches[1];
		}

		foreach ($range as $item) {
			$tests[] = new Job($file, $this->php, $item === NULL ? NULL : escapeshellarg($item));
		}
	}



	/**
	 * Writes to log
	 * @return string
	 */
	private function log($s)
	{
		if ($this->logFile) {
			fputs($this->logFile, "$s\n");
		}
		return "$s\n";
	}



	/**
	 * @return string
	 */
	private function format($s, Job $job, $message)
	{
		return "\n-- $s: {$job->getName()}"
			. ($job->getArguments() ? " [{$job->getArguments()}]" : '') . ' | '
			. implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $job->getFile()), -3))
			. str_replace("\n", "\n   ", "\n" . trim($message)) . "\n";
	}

}
