<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

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
	private const HTTP_OK = 200;

	/** @var Runner */
	private $runner;


	public function __construct(Runner $runner)
	{
		$this->runner = $runner;
	}


	public function initiate(string $file): void
	{
		[$annotations, $title] = $this->getAnnotations($file);
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


	public function assess(Job $job): void
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


	private function initiateSkip(Test $test, string $message): Test
	{
		return $test->withResult(Test::SKIPPED, $message);
	}


	private function initiatePhpVersion(Test $test, string $version, PhpInterpreter $interpreter): ?Test
	{
		if (preg_match('#^(<=|<|==|=|!=|<>|>=|>)?\s*(.+)#', $version, $matches)
			&& version_compare($matches[2], $interpreter->getVersion(), $matches[1] ?: '>=')) {
			return $test->withResult(Test::SKIPPED, "Requires PHP $version.");
		}
		return null;
	}


	private function initiatePhpExtension(Test $test, string $value, PhpInterpreter $interpreter): ?Test
	{
		foreach (preg_split('#[\s,]+#', $value) as $extension) {
			if (!$interpreter->hasExtension($extension)) {
				return $test->withResult(Test::SKIPPED, "Requires PHP extension $extension.");
			}
		}
		return null;
	}


	private function initiatePhpIni(Test $test, string $pair, PhpInterpreter &$interpreter): void
	{
		[$name, $value] = explode('=', $pair, 2) + [1 => null];
		$interpreter = $interpreter->withPhpIniOption($name, $value);
	}


	private function initiateDataProvider(Test $test, string $provider)
	{
		try {
			[$dataFile, $query, $optional] = Tester\DataProvider::parseAnnotation($provider, $test->getFile());
			$data = Tester\DataProvider::load($dataFile, $query);
		} catch (\Exception $e) {
			return $test->withResult(empty($optional) ? Test::FAILED : Test::SKIPPED, $e->getMessage());
		}

		return array_map(function (string $item) use ($test, $dataFile): Test {
			return $test->withArguments(['dataprovider' => "$item|$dataFile"]);
		}, array_keys($data));
	}


	private function initiateMultiple(Test $test, $count): array
	{
		return array_map(function (int $i) use ($test): Test {
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

		$methods = TestCase::parseOutput($job->getTest()->stdout);
		if ($methods === null) {
			return $test->withResult(Test::FAILED, "Cannot list TestCase methods in file '{$test->getFile()}'. Do you call TestCase::run() in it?");
		} elseif (!$methods) {
			return $test->withResult(Test::SKIPPED, "TestCase in file '{$test->getFile()}' does not contain test methods.");
		}

		return array_map(function (string $method) use ($test): Test {
			return $test->withArguments(['method' => $method]);
		}, $methods);
	}


	private function assessExitCode(Job $job, $code): ?Test
	{
		$test = $job->getTest();
		$code = (int) $code;
		if ($job->getExitCode() === Job::CODE_SKIP) {
			$message = preg_match('#.*Skipped:\n(.*?)\z#s', $output = $test->stdout, $m)
				? $m[1]
				: $output;
			return $test->withResult(Test::SKIPPED, trim($message));

		} elseif ($job->getExitCode() === Job::CODE_TESTCASE) {
			$methods = TestCase::parseOutput($test->stdout);
			if ($methods === null) {
				return $test->withResult(Test::FAILED, "Cannot read TestCaseRunner output in file '{$test->getFile()}'.");
			} elseif (!$methods) {
				return $test->withResult(Test::SKIPPED, "TestCaseRunner in file '{$test->getFile()}' does not contain any test.");
			}
			foreach ($methods as $method) {
				$testVariety = $test->withArguments(['method' => $method]);
				$this->runner->prepareTest($testVariety);
				$this->runner->addJob(new Job($testVariety, $this->runner->getInterpreter(), $this->runner->getEnvironmentVariables()));
			}

		} elseif ($job->getExitCode() !== $code) {
			$message = $job->getExitCode() !== Job::CODE_FAIL ? "Exited with error code {$job->getExitCode()} (expected $code)" : '';
			return $test->withResult(Test::FAILED, trim($message . "\n" . $test->stdout));
		}
		return null;
	}


	private function assessHttpCode(Job $job, $code): ?Test
	{
		if (!$this->runner->getInterpreter()->isCgi()) {
			return null;
		}
		$headers = $job->getHeaders();
		$actual = (int) ($headers['Status'] ?? self::HTTP_OK);
		$code = (int) $code;
		return $code && $code !== $actual
			? $job->getTest()->withResult(Test::FAILED, "Exited with HTTP code $actual (expected $code)")
			: null;
	}


	private function assessOutputMatchFile(Job $job, string $file): ?Test
	{
		$file = dirname($job->getTest()->getFile()) . DIRECTORY_SEPARATOR . $file;
		if (!is_file($file)) {
			return $job->getTest()->withResult(Test::FAILED, "Missing matching file '$file'.");
		}
		return $this->assessOutputMatch($job, file_get_contents($file));
	}


	private function assessOutputMatch(Job $job, string $content): ?Test
	{
		$actual = $job->getTest()->stdout;
		if (!Tester\Assert::isMatching($content, $actual)) {
			[$content, $actual] = Tester\Assert::expandMatchingPatterns($content, $actual);
			Dumper::saveOutput($job->getTest()->getFile(), $actual, '.actual');
			Dumper::saveOutput($job->getTest()->getFile(), $content, '.expected');
			return $job->getTest()->withResult(Test::FAILED, 'Failed: output should match ' . Dumper::toLine($content));
		}
		return null;
	}


	private function getAnnotations(string $file): array
	{
		$annotations = Helpers::parseDocComment(file_get_contents($file));
		$testTitle = isset($annotations[0]) ? preg_replace('#^TEST:\s*#i', '', $annotations[0]) : null;
		return [$annotations, $testTitle];
	}
}
