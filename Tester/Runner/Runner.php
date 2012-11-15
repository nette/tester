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

		$tests = $this->findTests();
		if (!$tests) {
			echo $this->log("No tests found\n");
			return;
		}

		list($failed, $skipped) = $this->runTests($tests);

		if ($this->displaySkipped) {
			echo "\n", implode($skipped);
		}

		if ($failed) {
			echo "\n", implode($failed);
			echo $this->log("\nFAILURES! (" . count($tests) . ' tests, ' . count($failed) . ' failures, ' . count($skipped) . ' skipped)');
			return FALSE;

		} else {
			echo $this->log("\n\nOK (" . count($tests) . ' tests, ' . count($skipped) . ' skipped)');
			return TRUE;
		}
	}



	/**
	 * @return array
	 */
	private function runTests(array $tests)
	{
		$failed = $skipped = $running = array();

		while ($tests || $running) {
			for ($i = count($running); $tests && $i < $this->jobs; $i++) {
				list($file, $args) = array_shift($tests);
				$testCase = new Job($file, $args, $this->php);
				try {
					$parallel = ($this->jobs > 1) && (count($running) + count($tests) > 1);
					$running[] = $testCase->run(!$parallel);
				} catch (JobException $e) {
					echo 's';
					$skipped[] = $this->log($this->format('Skipped', $testCase, $e));
				}
			}
			if (count($running) > 1) {
				usleep(self::RUN_USLEEP); // stream_select() doesn't work with proc_open()
			}
			foreach ($running as $key => $testCase) {
				if ($testCase->isReady()) {
					try {
						$testCase->collect();
						echo '.';
						//$passed[] = array($testCase->getName(), $testCase->getFile());

					} catch (JobException $e) {
						if ($e->getCode() === JobException::SKIPPED) {
							echo 's';
							$skipped[] = $this->log($this->format('Skipped', $testCase, $e));

						} else {
							echo 'F';
							$failed[] = $this->log($this->format('FAILED', $testCase, $e));
						}
					}
					unset($running[$key]);
				}
			}
		}
		return array($failed, $skipped);
	}



	/**
	 * @return array
	 */
	private function findTests()
	{
		$tests = array();
		foreach ($this->paths as $path) {
			if (is_file($path)) {
				$files = array($path);
			} else {
				$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
			}
			foreach ($files as $file) {
				$file = (string) $file;
				$info = pathinfo($file);
				if (!isset($info['extension']) || $info['extension'] !== 'phpt') {
					continue;
				}

				$options = Job::parseOptions($file);
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
					foreach ($range as $item) {
						$tests[] = array($file, escapeshellarg($item));
					}

				} else {
					$tests[] = array($file, NULL);
				}
			}
		}
		return $tests;
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
	private function format($s, $testCase, $e)
	{
		return "\n-- $s: {$testCase->getName()}"
			. ($testCase->getArguments() ? " [{$testCase->getArguments()}]" : '') . ' | '
			. implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $testCase->getFile()), -3))
			. str_replace("\n", "\n   ", "\n" . trim($e->getMessage())) . "\n";
	}

}
