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

	/** count of lines to print */
	const PRINT_LINES = 15;

	/** @var array  paths to test files/directories */
	public $paths = array();

	/** @var bool  generate Test Anything Protocol? */
	public $displayTap = FALSE;

	/** @var bool  display skipped tests information? */
	public $displaySkipped = FALSE;

	/** @var int  run jobs in parallel */
	public $jobs = 1;

	/** @var resource */
	private $logFile;

	/** @var PhpExecutable */
	private $php;

	/** @var array */
	private $results;


	public function __construct(PhpExecutable $php, $logFile = NULL)
	{
		$this->php = $php;
		if ($logFile) {
			$this->printAndLog("Log: $logFile");
			$this->logFile = fopen($logFile, 'w');
		}
	}


	/**
	 * Runs all tests.
	 * @return void
	 */
	public function run()
	{
		$this->printAndLog($this->displayTap ? 'TAP version 13' : ('PHP ' . $this->php->getVersion() . ' | ' . $this->php->getCommandLine() . " | $this->jobs threads\n"));

		$time = -microtime(TRUE);
		$this->results = array(self::PASSED => NULL, self::SKIPPED => NULL, self::FAILED => NULL);
		$tests = $this->findTests();
		$count = count($tests) + count($this->results, 1) - count($this->results);
		if (!$count) {
			$this->printAndLog('No tests found');
			return;
		}

		$this->runTests($tests);
		$time += microtime(TRUE);

		if ($this->displayTap) {
			$this->printAndLog("1..$count");
			return;
		}

		$this->printAndLog("\n\n", FALSE);
		if ($this->displaySkipped && $this->results[self::SKIPPED]) {
			$this->printAndLog(implode("\n", $this->results[self::SKIPPED]), FALSE);
		}

		if ($this->results[self::FAILED]) {
			$this->printAndLog(implode("\n", $this->results[self::FAILED]), FALSE);
			$this->printAndLog("\nFAILURES! ($count tests, "
				. count($this->results[self::FAILED]) . ' failures, '
				. count($this->results[self::SKIPPED]) . ' skipped, ' . sprintf('%0.1f', $time) . ' seconds)');
			return FALSE;

		} else {
			$this->printAndLog("OK ($count tests, "
				. count($this->results[self::SKIPPED]) . ' skipped, ' . sprintf('%0.1f', $time) . ' seconds)');
			return TRUE;
		}
	}


	/**
	 * @return void
	 */
	private function runTests(array $tests)
	{
		$running = array();
		while ($tests || $running) {
			for ($i = count($running); $tests && $i < $this->jobs; $i++) {
				try {
					$running[] = $job = array_shift($tests);
					$job->run($this->jobs <= 1 || (count($running) + count($tests) <= 1));
				} catch (JobException $e) {
					$this->printAndLogResult(self::SKIPPED, $job, $e->getMessage());
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
					$this->printAndLogResult(self::PASSED, $job);

				} catch (JobException $e) {
					$this->printAndLogResult($e->getCode() === Job::CODE_SKIP ? self::SKIPPED : self::FAILED, $job, $e->getMessage());
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
			$path = realpath($path);
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
			return $this->printAndLogResult(self::SKIPPED, $job, $options['skip']);

		} elseif (isset($options['phpversion'])) {
			foreach ((array) $options['phpversion'] as $phpVersion) {
				if (preg_match('#^(<=|<|==|=|!=|<>|>=|>)?\s*(.+)#', $phpVersion, $matches)
					&& version_compare($matches[2], $this->php->getVersion(), $matches[1] ?: '>='))
				{
					return $this->printAndLogResult(self::SKIPPED, $job, "Requires PHP $phpVersion.");
				}
			}
		}

		if (isset($options['dataprovider?'])) {
			$options['dataprovider'] = $options['dataprovider?'];
		}
		if (isset($options['dataprovider'])) {
			list($dataFile, $query) = preg_split('#\s*,?\s+#', $options['dataprovider'], 2) + array('', '');
			try {
				$range = array_keys(Tester\DataProvider::load(dirname($file) . '/' . $dataFile, $query));
			} catch (\Exception $e) {
				return $this->printAndLogResult(isset($options['dataprovider?']) ? self::SKIPPED : self::FAILED, $job, $e->getMessage());
			}

		} elseif (isset($options['multiple'])) {
			$range = range(0, $options['multiple'] - 1);

		} elseif (isset($options['testcase']) && preg_match_all('#\sfunction\s+(test\w+)\(#', file_get_contents($file), $matches)) {
			$range = $matches[1];
		}

		foreach ($range as $item) {
			$tests[] = new Job($file, $this->php, $item === NULL ? NULL : escapeshellarg($item));
		}
	}


	/**
	 * Prints and writes to log.
	 * @return void
	 */
	private function printAndLog($s, $log = TRUE)
	{
		if (strlen($s) > 1) {
			$s .= "\n";
		}

		if (Tester\Environment::$useColors) {
			$repl = array(
				'#^OK .*#m' => "\033[1;42;1;37m\\0\033[0m",
				'#^FAILURES! .*#m' => "\033[1;41;37m\\0\033[0m",
				'#^F\z#' => "\033[1;41;37m\\0\033[0m",
				'#^-- FAILED: .*#m' => "\033[1;31m\\0\033[0m",
			);
			$s = preg_replace(array_keys($repl), $repl, $s);
		}
		echo $s;

		if ($this->logFile && $log) {
			fputs($this->logFile, Tester\Dumper::removeColors($s));
		}
	}


	/**
	 * @return void
	 */
	private function printAndLogResult($result, Job $job, $message = NULL)
	{
		$outputs = $this->displayTap ? array(
			self::PASSED => "ok {$job->getName()}",
			self::SKIPPED => "ok {$job->getName()} #skip $message",
			self::FAILED => "not ok {$job->getName()}" . str_replace("\n", "\n# ", "\n" . trim($message)),
		) : array(
			self::PASSED => '.',
			self::SKIPPED => 's',
			self::FAILED => 'F',
		);
		$this->printAndLog($outputs[$result], FALSE);

		$outputs = array(
			self::PASSED => "-- OK: {$job->getName()}",
			self::SKIPPED => "-- Skipped: {$job->getName()}\n   $message",
			self::FAILED => "-- FAILED: {$job->getName()}" . str_replace("\n", "\n   ", "\n" . trim($message)),
		);
		if ($this->logFile) {
			fputs($this->logFile, Tester\Dumper::removeColors($outputs[$result]) . "\n\n");
		}
		$lines = explode("\n", $outputs[$result], self::PRINT_LINES + 1);
		$lines[self::PRINT_LINES] = isset($lines[self::PRINT_LINES]) ? "\n   ..." : '';
		$this->results[$result][] = implode("\n", $lines);
	}

}
