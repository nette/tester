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
				$running[] = $job = array_shift($tests);
				$job->run($this->jobs <= 1 || (count($running) + count($tests) <= 1));
			}

			if (count($running) > 1) {
				usleep(self::RUN_USLEEP); // stream_select() doesn't work with proc_open()
			}

			foreach ($running as $key => $job) {
				if ($job->isReady()) {
					$job->collect();
					$this->processResult($job);
					unset($running[$key]);
				}
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
		$options = Tester\Helpers::parseDocComment(file_get_contents($file));
		$options['name'] = $name = (isset($options[0]) ? preg_replace('#^TEST:\s*#i', '', $options[0]) . ' | ' : '')
			. implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $file), -3));
		$range = array(NULL);

		if (isset($options['skip'])) {
			return $this->printAndLogResult($name, self::SKIPPED, $options['skip']);

		} elseif (isset($options['phpversion'])) {
			foreach ((array) $options['phpversion'] as $phpVersion) {
				if (preg_match('#^(<=|<|==|=|!=|<>|>=|>)?\s*(.+)#', $phpVersion, $matches)
					&& version_compare($matches[2], $this->php->getVersion(), $matches[1] ?: '>='))
				{
					return $this->printAndLogResult($name, self::SKIPPED, "Requires PHP $phpVersion.");
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
				return $this->printAndLogResult($name, isset($options['dataprovider?']) ? self::SKIPPED : self::FAILED, $e->getMessage());
			}

		} elseif (isset($options['multiple'])) {
			$range = range(0, $options['multiple'] - 1);

		} elseif (isset($options['testcase']) && preg_match_all('#\sfunction\s+(test\w+)\(#', file_get_contents($file), $matches)) {
			$range = $matches[1];
		}

		$php = clone $this->php;
		if (isset($options['phpini'])) {
			foreach ((array) $options['phpini'] as $item) {
				$php->arguments .= ' -d ' . escapeshellarg(trim($item));
			}
		}

		foreach ($range as $item) {
			$tests[] = $job = new Job($file, $php, $item === NULL ? NULL : escapeshellarg($item));
			$job->options = $options;
			$job->options['name'] .= $item ? " [$item]" : '';
		}
	}



	/**
	 * Checks test results.
	 * @return void
	 */
	private function processResult(Job $job)
	{
		$options = $job->options;
		$name = $options['name'];

		if ($job->getExitCode() === Job::CODE_SKIP) {
			$lines = explode("\n", trim($job->getOutput()));
			return $this->printAndLogResult($name, self::SKIPPED, end($lines));
		}

		$expected = isset($options['exitcode']) ? (int) $options['exitcode'] : Job::CODE_OK;
		if ($job->getExitCode() !== $expected) {
			return $this->printAndLogResult($name, self::FAILED, ($job->getExitCode() !== Job::CODE_FAIL ? "Exited with error code {$job->getExitCode()} (expected $expected)\n" : '') . $job->getOutput());
		}

		if ($this->php->isCgi()) {
			$headers = $job->getHeaders();
			$code = isset($headers['Status']) ? (int) $headers['Status'] : 200;
			$expected = isset($options['httpcode']) ? (int) $options['httpcode'] : (isset($options['assertcode']) ? (int) $options['assertcode'] : $code);
			if ($expected && $code !== $expected) {
				return $this->printAndLogResult($name, self::FAILED, "Exited with HTTP code $code (expected $expected})");
			}
		}

		if (isset($options['outputmatchfile'])) {
			$file = dirname($job->getFile()) . '/' . $options['outputmatchfile'];
			if (!is_file($file)) {
				return $this->printAndLogResult($name, self::FAILED, "Missing matching file '$file'.");
			}
			$options['outputmatch'] = file_get_contents($file);
		} elseif (isset($options['outputmatch']) && !is_string($options['outputmatch'])) {
			$options['outputmatch'] = '';
		}

		if (isset($options['outputmatch']) && !Tester\Assert::isMatching($options['outputmatch'], $job->getOutput())) {
			Tester\Helpers::dumpOutput($job->getFile(), $job->getOutput(), '.actual');
			Tester\Helpers::dumpOutput($job->getFile(), $options['outputmatch'], '.expected');
			return $this->printAndLogResult($name, self::FAILED, 'Failed: output should match ' . Tester\Dumper::toLine($options['outputmatch']));
		}

		return $this->printAndLogResult($name, self::PASSED);
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
	private function printAndLogResult($name, $result, $message = NULL)
	{
		$outputs = $this->displayTap ? array(
			self::PASSED => "ok $name",
			self::SKIPPED => "ok $name #skip $message",
			self::FAILED => "not ok $name" . str_replace("\n", "\n# ", "\n" . trim($message)),
		) : array(
			self::PASSED => '.',
			self::SKIPPED => 's',
			self::FAILED => 'F',
		);
		$this->printAndLog($outputs[$result], FALSE);

		$outputs = array(
			self::PASSED => "-- OK: $name",
			self::SKIPPED => "-- Skipped: $name\n   $message",
			self::FAILED => "-- FAILED: $name" . str_replace("\n", "\n   ", "\n" . trim($message)),
		);
		if ($this->logFile) {
			fputs($this->logFile, Tester\Dumper::removeColors($outputs[$result]) . "\n\n");
		}
		$lines = explode("\n", $outputs[$result], self::PRINT_LINES + 1);
		$lines[self::PRINT_LINES] = isset($lines[self::PRINT_LINES]) ? "\n   ..." : '';
		$this->results[$result][] = implode("\n", $lines);
	}

}
