<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester;
use Tester\Dumper;
use Tester\Helpers;
use Tester\TestCase;


/**
 * Default test behavior.
 */
class TestHandler
{
	const HTTP_OK = 200;

	/** @var Runner */
	private $runner;


	public function __construct(Runner $runner)
	{
		$this->runner = $runner;
	}


	/**
	 * @param  string
	 * @return void
	 */
	public function initiate($file)
	{
		list($annotations, $title) = $this->getAnnotations($file);
		$php = $this->runner->getInterpreter();

		$tests = [new Test($file, $title)];
		foreach (get_class_methods($this) as $method) {
			if (!preg_match('#^initiate(.+)#', strtolower($method), $m) || !isset($annotations[$m[1]])) {
				continue;
			}

			foreach ((array) $annotations[$m[1]] as $value) {
				/** @var Test[] $prepared */
				$prepared = [];
				foreach ($tests as $test) {
					$res = $this->$method($test, $value, $php);
					if ($res === null) {
						$prepared[] = $test;
					} else {
						foreach (is_array($res) ? $res : [$res] as $testVariety) {
							/** @var Test $testVariety */
							if ($testVariety->hasResult()) {
								$this->runner->prepareTest($testVariety);
								$this->runner->finishTest($testVariety);
							} else {
								$prepared[] = $testVariety;
							}
						}
					}
				}
				$tests = $prepared;
			}
		}

		foreach ($tests as $test) {
			$this->runner->prepareTest($test);
			$this->runner->addJob(new Job($test, $php, $this->runner->getEnvironmentVariables()));
		}
	}


	/**
	 * @return void
	 */
	public function assess(Job $job)
	{
		$test = $job->getTest();
		$annotations = $this->getAnnotations($test->getFile())[0] += [
			'exitcode' => Job::CODE_OK,
			'httpcode' => self::HTTP_OK,
		];

		foreach (get_class_methods($this) as $method) {
			if (!preg_match('#^assess(.+)#', strtolower($method), $m) || !isset($annotations[$m[1]])) {
				continue;
			}

			foreach ((array) $annotations[$m[1]] as $arg) {
				/** @var Test|null $res */
				if ($res = $this->$method($job, $arg)) {
					$this->runner->finishTest($res);
					return;
				}
			}
		}
		$this->runner->finishTest($test->withResult(Test::PASSED, $test->message));
	}


	private function initiateSkip(Test $test, $message)
	{
		return $test->withResult(Test::SKIPPED, $message);
	}


	private function initiatePhpVersion(Test $test, $version, PhpInterpreter $interpreter)
	{
		if (preg_match('#^(<=|<|==|=|!=|<>|>=|>)?\s*(.+)#', $version, $matches)
			&& version_compare($matches[2], $interpreter->getVersion(), $matches[1] ?: '>=')) {
			return $test->withResult(Test::SKIPPED, "Requires PHP $version.");
		}
	}


	private function initiatePhpExtension(Test $test, $value, PhpInterpreter $interpreter)
	{
		foreach (preg_split('#[\s,]+#', $value) as $extension) {
			if (!$interpreter->hasExtension($extension)) {
				return $test->withResult(Test::SKIPPED, "Requires PHP extension $extension.");
			}
		}
	}


	private function initiatePhpIni(Test $test, $pair, PhpInterpreter &$interpreter)
	{
		list($name, $value) = explode('=', $pair, 2) + [1 => null];
		$interpreter = $interpreter->withPhpIniOption($name, $value);
	}


	private function initiateDataProvider(Test $test, $provider)
	{
		try {
			list($dataFile, $query, $optional) = Tester\DataProvider::parseAnnotation($provider, $test->getFile());
			$data = Tester\DataProvider::load($dataFile, $query);
		} catch (\Exception $e) {
			return $test->withResult(empty($optional) ? Test::FAILED : Test::SKIPPED, $e->getMessage());
		}

		return array_map(function ($item) use ($test, $dataFile) {
			return $test->withArguments(['dataprovider' => "$item|$dataFile"]);
		}, array_keys($data));
	}


	private function initiateMultiple(Test $test, $count)
	{
		return array_map(function ($i) use ($test) {
			return $test->withArguments(['multiple' => $i]);
		}, range(0, (int) $count - 1));
	}


	private function initiateTestCase(Test $test, $foo, PhpInterpreter $interpreter)
	{
		$job = new Job($test->withArguments(['method' => TestCase::LIST_METHODS]), $interpreter);
		$job->run();

		if (in_array($job->getExitCode(), [Job::CODE_ERROR, Job::CODE_FAIL, Job::CODE_SKIP], true)) {
			return $test->withResult($job->getExitCode() === Job::CODE_SKIP ? Test::SKIPPED : Test::FAILED, $job->getTest()->stdout);
		}

		if (!preg_match('#\[([^[]*)]#', (string) strrchr($job->getTest()->stdout, '['), $m)) {
			return $test->withResult(Test::FAILED, "Cannot list TestCase methods in file '{$test->getFile()}'. Do you call TestCase::run() in it?");
		} elseif (!strlen($m[1])) {
			return $test->withResult(Test::SKIPPED, "TestCase in file '{$test->getFile()}' does not contain test methods.");
		}

		return array_map(function ($method) use ($test) {
			return $test->withArguments(['method' => $method]);
		}, explode(',', $m[1]));
	}


	private function assessExitCode(Job $job, $code)
	{
		$code = (int) $code;
		if ($job->getExitCode() === Job::CODE_SKIP) {
			$message = preg_match('#.*Skipped:\n(.*?)\z#s', $output = $job->getTest()->stdout, $m)
				? $m[1]
				: $output;
			return $job->getTest()->withResult(Test::SKIPPED, trim($message));

		} elseif ($job->getExitCode() !== $code) {
			$message = $job->getExitCode() !== Job::CODE_FAIL ? "Exited with error code {$job->getExitCode()} (expected $code)" : '';
			return $job->getTest()->withResult(Test::FAILED, trim($message . "\n" . $job->getTest()->stdout));
		}
	}


	private function assessHttpCode(Job $job, $code)
	{
		if (!$this->runner->getInterpreter()->isCgi()) {
			return;
		}
		$headers = $job->getHeaders();
		$actual = isset($headers['Status']) ? (int) $headers['Status'] : self::HTTP_OK;
		$code = (int) $code;
		if ($code && $code !== $actual) {
			return $job->getTest()->withResult(Test::FAILED, "Exited with HTTP code $actual (expected $code)");
		}
	}


	private function assessOutputMatchFile(Job $job, $file)
	{
		$file = dirname($job->getTest()->getFile()) . DIRECTORY_SEPARATOR . $file;
		if (!is_file($file)) {
			return $job->getTest()->withResult(Test::FAILED, "Missing matching file '$file'.");
		}
		return $this->assessOutputMatch($job, file_get_contents($file));
	}


	private function assessOutputMatch(Job $job, $content)
	{
		$actual = $job->getTest()->stdout;
		if (!Tester\Assert::isMatching($content, $actual)) {
			list($content, $actual) = Tester\Assert::expandMatchingPatterns($content, $actual);
			Dumper::saveOutput($job->getTest()->getFile(), $actual, '.actual');
			Dumper::saveOutput($job->getTest()->getFile(), $content, '.expected');
			return $job->getTest()->withResult(Test::FAILED, 'Failed: output should match ' . Dumper::toLine($content));
		}
	}


	private function getAnnotations($file)
	{
		$annotations = Helpers::parseDocComment(file_get_contents($file));
		$testTitle = isset($annotations[0]) ? preg_replace('#^TEST:\s*#i', '', $annotations[0]) : null;
		return [$annotations, $testTitle];
	}
}
