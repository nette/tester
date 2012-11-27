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
	const
		PASSED = 1,
		SKIPPED = 2,
		FAILED = 3;

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
	private $results;



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

		$this->results = array(self::PASSED => NULL, self::SKIPPED => NULL, self::FAILED => NULL);
		$tests = $this->findTests();
		if (!$tests && !$this->results[self::SKIPPED]) {
			echo $this->log("No tests found\n");
			return;
		}

		$this->runTests($tests);

		if ($this->displaySkipped) {
			echo "\n", implode($this->results[self::SKIPPED]);
		}

		if ($this->results[self::FAILED]) {
			echo "\n", implode($this->results[self::FAILED]);
			echo $this->log("\nFAILURES! (" . count($tests) . ' tests, '
				. count($this->results[self::FAILED]) . ' failures, '
				. count($this->results[self::SKIPPED]) . ' skipped)');
			return FALSE;

		} else {
			echo $this->log("\n\nOK (" . count($tests) . ' tests, '
				. count($this->results[self::SKIPPED]) . ' skipped)');
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
				$job = array_shift($tests);
				try {
					$parallel = ($this->jobs > 1) && (count($running) + count($tests) > 1);
					$running[] = $job->run(!$parallel);
				} catch (JobException $e) {
					$this->logResult(self::SKIPPED, $job, $e->getMessage());
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
					$this->logResult(self::PASSED, $job);

				} catch (JobException $e) {
					$this->logResult($e->getCode() === JobException::SKIPPED ? self::SKIPPED : self::FAILED, $job, $e->getMessage());
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
			return $this->logResult(self::SKIPPED, $job, $options['skip']);

		} elseif (isset($options['phpversion'])) {
			$operator = '>=';
			if (preg_match('#^(<=|le|<|lt|==|=|eq|!=|<>|ne|>=|ge|>|gt)#', $options['phpversion'], $matches)) {
				$options['phpversion'] = trim(substr($options['phpversion'], strlen($matches[1])));
				$operator = $matches[1];
			}
			if (version_compare($options['phpversion'], $this->php->getVersion(), $operator)) {
				return $this->logResult(self::SKIPPED, $job, "Requires PHP $operator {$options['phpversion']}.");
			}
		}

		if (isset($options['dataprovider?'])) {
			$options['dataprovider'] = $options['dataprovider?'];
		}
		if (isset($options['dataprovider'])) {
			if (!preg_match('#^(\S+)(?:\s+\[(.+)\])?\z#', $options['dataprovider'], $m)) {
				return $this->logResult(self::FAILED, $job, "Syntax error in '$options[dataprovider]' annotation.");
			}

			$requiredTags = isset($m[2]) ? $this->parseTagsStr($m[2]) : array();

			if (!is_file($dataFile = dirname($file) . '/' . $m[1])) {
				return $this->logResult(isset($options['dataprovider?']) ? self::SKIPPED : self::FAILED, $job, "Missing @dataProvider configuration file '$dataFile'.");

			} elseif (($dataProvider = $this->parseTaggedIniFile($dataFile)) === FALSE) {
				return $this->logResult(self::FAILED, $job, "Cannot parse @dataProvider configuration file '$dataFile'.");
			}

			$range = array();
			foreach ($dataProvider as $name => $params) {
				if ($this->tagsMatch($requiredTags, $params['tags'])) {
					$range[] = $name;
				}
			}

			if (!$range) {
				return $this->logResult(self::SKIPPED, $job, "Set of '@dataProvider $options[dataprovider]' is empty for test.");
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
	 * Parses tags string "name op val, ..." used in @dataProvider annotation.
	 * @param  string
	 * @param  string  default operator
	 * @return array[] of [name, op, val]
	 */
	private function parseTagsStr($str, $operator = '=')
	{
		static $reOps = '<=|<|==|=|!=|<>|>=|>';
		static $reLetterOps = 'le|lt|eq|ne|ge|gt';

		$res = array();

		$tags = preg_split('#\s*,\s*#', $str, -1, PREG_SPLIT_NO_EMPTY);
		foreach ($tags as $tag) {
			$parts = preg_split("#\s*((?:$reOps)|(?:\s(?:$reLetterOps)\s))\s*#", $tag, 2, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

			$res[] = array(
				$parts[0],
				isset($parts[1]) ? trim($parts[1]) : $operator,
				isset($parts[2]) ? $parts[2] : TRUE,
			);
		}

		return $res;
	}



	/**
	 * Parses INI file which contents tags in comment before section (;@ tags, ...).
	 * @param  string  path to INI file
	 * @return array
	 */
	private function parseTaggedIniFile($file)
	{
		if (($raw = @file_get_contents($file)) === FALSE) {
			return FALSE;
		}

		if (($sections = @parse_ini_string($raw, TRUE)) === FALSE) {
			return FALSE;
		}

		foreach ($sections as $name => $args) {
			$tags = array();
			if (preg_match('#;@([^\r\n]+)\n\[' . preg_quote($name, '#') . '\]#', $raw, $m)) {
				foreach (explode(',', preg_replace('#\s#', '', $m[1])) as $tag) {
					$parts = explode('=', $tag, 2);
					$tags[$parts[0]] = isset($parts[1]) ? $parts[1] : TRUE;
				}
			}

			$sections[$name] = array(
				'tags' => $tags,
				'args' => $args,
			);
		}

		return $sections;
	}



	/**
	 * Compares tags.
	 * @param  array[] of [name, op, val]  required tags
	 * @param  array[name] => val  offered tags
	 * @return bool
	 */
	private function tagsMatch(array $required, array $offered)
	{
		foreach ($required as $req) {
			list($name, $operator, $value) = $req;

			if (!array_key_exists($name, $offered)) {
				return FALSE;

			} elseif ($value === TRUE) { // flag
				continue;
			}

			switch ($operator) {
				case '==': case '=': case 'eq':
					if (strcmp((string) $offered[$name], (string) $value) !== 0) {
						return FALSE;
					}
					break;

				case '!=': case '<>': case 'ne':
					if (strcmp((string) $offered[$name], (string) $value) === 0) {
						return FALSE;
					}
					break;

				case '<=': case 'le': case '<': case 'lt':
				case '>=': case 'ge': case '>':	case 'gt':
					if (!version_compare($offered[$name], $value, $operator)) {
						return FALSE;
					}
					break;

				default:
					throw new \Exception("Unsupported operator '$operator'.");
			}
		}

		return TRUE;
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
	 * @return void
	 */
	private function logResult($result, Job $job, $message = NULL)
	{
		static $results = array(
			self::PASSED => array('.'),
			self::SKIPPED => array('s', 'Skipped'),
			self::FAILED => array('F', 'FAILED'),
		);

		echo $results[$result][0];

		$file = implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $job->getFile()), -3));
		if ($result === self::PASSED) {
			$this->results[$result][] = "{$job->getName()} $file";
		} else {
			$this->results[$result][] = $this->log("\n-- {$results[$result][1]}: {$job->getName()}"
				. ($job->getArguments() ? " [{$job->getArguments()}]" : '')
				. " | $file" . str_replace("\n", "\n   ", "\n" . trim($message)) . "\n");
		}
	}

}
