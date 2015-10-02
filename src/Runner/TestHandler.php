<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester;
use Tester\Dumper;
use Tester\Helpers;


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
	 * @return void
	 */
	public function initiate($file)
	{
		list($annotations, $testName) = $this->getAnnotations($file);
		$php = clone $this->runner->getInterpreter();
		$jobsArgs = [[]];

		foreach (get_class_methods($this) as $method) {
			if (!preg_match('#^initiate(.+)#', strtolower($method), $m) || !isset($annotations[$m[1]])) {
				continue;
			}
			foreach ((array) $annotations[$m[1]] as $value) {
				$res = $this->$method($value, $php, $file);
				if ($res && is_int($res[0])) { // [Runner::*, message]
					$this->runner->writeResult($testName, $res[0], $res[1]);
					return;
				} elseif ($res && $res[1]) { // [param name, values]
					$tmp = [];
					foreach ($res[1] as $val) {
						foreach ($jobsArgs as $args) {
							$args[] = Helpers::escapeArg("--$res[0]=$val");
							$tmp[] = $args;
						}
					}
					$jobsArgs = $tmp;
				}
			}
		}

		foreach ($jobsArgs as $args) {
			$this->runner->addJob(new Job($file, $php, $args));
		}
	}


	/**
	 * @return void
	 */
	public function assess(Job $job)
	{
		list($annotations, $testName) = $this->getAnnotations($job->getFile());
		$testName .= $job->getArguments()
			? ' [' . implode(' ', preg_replace(['#["\'-]*(.+?)["\']?$#A', '#(.{30}).+#A'], ['$1', '$1...'], $job->getArguments())) . ']'
			: '';
		$annotations += [
			'exitcode' => Job::CODE_OK,
			'httpcode' => self::HTTP_OK,
		];

		foreach (get_class_methods($this) as $method) {
			if (!preg_match('#^assess(.+)#', strtolower($method), $m) || !isset($annotations[$m[1]])) {
				continue;
			}
			foreach ((array) $annotations[$m[1]] as $arg) {
				if ($res = $this->$method($job, $arg)) {
					$this->runner->writeResult($testName, $res[0], $res[1]);
					return;
				}
			}
		}
		$this->runner->writeResult($testName, Runner::PASSED);
	}


	private function initiateSkip($message)
	{
		return [Runner::SKIPPED, $message];
	}


	private function initiatePhpVersion($version, PhpInterpreter $interpreter)
	{
		if (preg_match('#^(<=|<|==|=|!=|<>|>=|>)?\s*(.+)#', $version, $matches)
			&& version_compare($matches[2], $interpreter->getVersion(), $matches[1] ?: '>='))
		{
			return [Runner::SKIPPED, "Requires PHP $version."];
		}
	}


	private function initiatePhpIni($value, PhpInterpreter $interpreter)
	{
		$interpreter->arguments .= ' -d ' . Helpers::escapeArg($value);
	}


	private function initiateDataProvider($provider, PhpInterpreter $interpreter, $file)
	{
		try {
			list($dataFile, $query, $optional) = Tester\DataProvider::parseAnnotation($provider, $file);
			$data = Tester\DataProvider::load($dataFile, $query);
		} catch (\Exception $e) {
			return [empty($optional) ? Runner::FAILED : Runner::SKIPPED, $e->getMessage()];
		}

		$res = [];
		foreach (array_keys($data) as $item) {
			$res[] = "$item|$dataFile";
		}
		return ['dataprovider', $res];
	}


	private function initiateMultiple($count, PhpInterpreter $interpreter, $file)
	{
		return ['multiple', range(0, (int) $count - 1)];
	}


	private function initiateTestCase($foo, PhpInterpreter $interpreter, $file)
	{
		$job = new Job($file, $interpreter, [Helpers::escapeArg('--method=' . Tester\TestCase::LIST_METHODS)]);
		$job->run();

		if (in_array($job->getExitCode(), [Job::CODE_ERROR, Job::CODE_FAIL, Job::CODE_SKIP], TRUE)) {
			return [$job->getExitCode() === Job::CODE_SKIP ? Runner::SKIPPED : Runner::FAILED, $job->getOutput()];
		}

		if (!preg_match('#\[([^[]*)]#', strrchr($job->getOutput(), '['), $m)) {
			return [Runner::FAILED, "Cannot list TestCase methods in file '$file'. Do you call TestCase::run() in it?"];
		} elseif (!strlen($m[1])) {
			return [Runner::SKIPPED, "TestCase in file '$file' does not contain test methods."];
		}

		return ['method', explode(',', $m[1])];
	}


	private function assessExitCode(Job $job, $code)
	{
		$code = (int) $code;
		if ($job->getExitCode() === Job::CODE_SKIP) {
			$message = preg_match('#.*Skipped:\n(.*?)\z#s', $output = $job->getOutput(), $m)
				? $m[1]
				: $output;
			return [Runner::SKIPPED, trim($message)];

		} elseif ($job->getExitCode() !== $code) {
			$message = $job->getExitCode() !== Job::CODE_FAIL ? "Exited with error code {$job->getExitCode()} (expected $code)" : '';
			return [Runner::FAILED, trim($message . "\n" . $job->getOutput())];
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
			return [Runner::FAILED, "Exited with HTTP code $actual (expected $code)"];
		}
	}


	private function assessOutputMatchFile(Job $job, $file)
	{
		$file = dirname($job->getFile()) . DIRECTORY_SEPARATOR . $file;
		if (!is_file($file)) {
			return [Runner::FAILED, "Missing matching file '$file'."];
		}
		return $this->assessOutputMatch($job, file_get_contents($file));
	}


	private function assessOutputMatch(Job $job, $content)
	{
		if (!Tester\Assert::isMatching($content, $job->getOutput())) {
			Dumper::saveOutput($job->getFile(), $job->getOutput(), '.actual');
			Dumper::saveOutput($job->getFile(), $content, '.expected');
			return [Runner::FAILED, 'Failed: output should match ' . Dumper::toLine(rtrim($content))];
		}
	}


	private function getAnnotations($file)
	{
		$annotations = Helpers::parseDocComment(file_get_contents($file));
		$testName = (isset($annotations[0]) ? preg_replace('#^TEST:\s*#i', '', $annotations[0]) . ' | ' : '')
			. implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $file), -3));
		return [$annotations, $testName];
	}

}
