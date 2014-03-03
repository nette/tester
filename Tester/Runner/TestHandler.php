<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester,
	Tester\Dumper,
	Tester\Helpers;


/**
 * Default test behavior.
 *
 * @author     David Grudl
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
		$php = clone $this->runner->getPhp();
		$job = FALSE;

		foreach (get_class_methods($this) as $method) {
			if (!preg_match('#^initiate(.+)#', strtolower($method), $m) || !isset($annotations[$m[1]])) {
				continue;
			}
			foreach ((array) $annotations[$m[1]] as $arg) {
				$res = $this->$method($arg, $php, $file);
				if ($res === TRUE) {
					$job = TRUE;
				} elseif ($res) {
					$this->runner->writeResult($testName, $res[0], $res[1]);
					return;
				}
			}
		}

		if (!$job) {
			$this->runner->addJob(new Job($file, $php));
		}
	}


	/**
	 * @return void
	 */
	public function assess(Job $job)
	{
		list($annotations, $testName) = $this->getAnnotations($job->getFile());
		$testName .= (strlen($job->getArguments()) ? " [{$job->getArguments()}]" : '');
		$annotations += array(
			'exitcode' => Job::CODE_OK,
			'httpcode' => self::HTTP_OK,
		);

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
		return array(Runner::SKIPPED, $message);
	}


	private function initiatePhpVersion($version, IPhpInterpreter $php)
	{
		if (preg_match('#^(<=|<|==|=|!=|<>|>=|>)?\s*(.+)#', $version, $matches)
			&& version_compare($matches[2], $php->getVersion(), $matches[1] ?: '>='))
		{
			return array(Runner::SKIPPED, "Requires PHP $version.");
		}
	}


	private function initiatePhpIni($value, IPhpInterpreter $php)
	{
		$php->addArgument('-d', $value);
	}


	private function initiateDataProvider($provider, IPhpInterpreter $php, $file)
	{
		try {
			list($dataFile, $query, $optional) = Tester\DataProvider::parseAnnotation($provider, $file);
			$data = Tester\DataProvider::load($dataFile, $query);
		} catch (\Exception $e) {
			return array(empty($optional) ? Runner::FAILED : Runner::SKIPPED, $e->getMessage());
		}

		foreach (array_keys($data) as $item) {
			$this->runner->addJob(new Job($file, $php, Helpers::escapeArg($item) . ' ' . Helpers::escapeArg($dataFile)));
		}
		return TRUE;
	}


	private function initiateMultiple($count, IPhpInterpreter $php, $file)
	{
		foreach (range(0, (int) $count - 1) as $arg) {
			$this->runner->addJob(new Job($file, $php, (string) $arg));
		}
		return TRUE;
	}


	private function initiateTestCase($foo, IPhpInterpreter $php, $file)
	{
		$job = new Job($file, $php, Helpers::escapeArg(Tester\TestCase::LIST_METHODS));
		$job->run();

		if (in_array($job->getExitCode(), array(Job::CODE_ERROR, Job::CODE_FAIL, Job::CODE_SKIP))) {
			return array($job->getExitCode() === Job::CODE_SKIP ? Runner::SKIPPED : Runner::FAILED, $job->getOutput());
		}

		$methods = json_decode(strrchr($job->getOutput(), '['));
		if (!is_array($methods)) {
			return array(Runner::FAILED, "Cannot list TestCase methods in file '$file'. Do you call TestCase::run() in it?");
		} elseif (!$methods) {
			return array(Runner::SKIPPED, "TestCase in file '$file' does not contain test methods.");
		}

		foreach ($methods as $method) {
			$this->runner->addJob(new Job($file, $php, Helpers::escapeArg($method)));
		}
		return TRUE;
	}


	private function assessExitCode(Job $job, $code)
	{
		$code = (int) $code;
		if ($job->getExitCode() === Job::CODE_SKIP) {
			$message = preg_match('#.*Skipped:\n(.*?)\z#s', $output = $job->getOutput(), $m)
				? $m[1]
				: $output;
			return array(Runner::SKIPPED, trim($message));

		} elseif ($job->getExitCode() !== $code) {
			$message = $job->getExitCode() !== Job::CODE_FAIL ? "Exited with error code {$job->getExitCode()} (expected $code)" : '';
			return array(Runner::FAILED, trim($message . "\n" . $job->getOutput()));
		}
	}


	private function assessHttpCode(Job $job, $code)
	{
		if (!$this->runner->getPhp()->isCgi()) {
			return;
		}
		$headers = $job->getHeaders();
		$actual = isset($headers['Status']) ? (int) $headers['Status'] : self::HTTP_OK;
		$code = (int) $code;
		if ($code && $code !== $actual) {
			return array(Runner::FAILED, "Exited with HTTP code $actual (expected $code)");
		}
	}


	private function assessOutputMatchFile(Job $job, $file)
	{
		$file = dirname($job->getFile()) . DIRECTORY_SEPARATOR . $file;
		if (!is_file($file)) {
			return array(Runner::FAILED, "Missing matching file '$file'.");
		}
		return $this->assessOutputMatch($job, file_get_contents($file));
	}


	private function assessOutputMatch(Job $job, $content)
	{
		if (!Tester\Assert::isMatching($content, $job->getOutput())) {
			Dumper::saveOutput($job->getFile(), $job->getOutput(), '.actual');
			Dumper::saveOutput($job->getFile(), $content, '.expected');
			return array(Runner::FAILED, 'Failed: output should match ' . Dumper::toLine(rtrim($content)));
		}
	}


	private function getAnnotations($file)
	{
		$annotations = Helpers::parseDocComment(file_get_contents($file));
		$testName = (isset($annotations[0]) ? preg_replace('#^TEST:\s*#i', '', $annotations[0]) . ' | ' : '')
			. implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $file), -3));
		return array($annotations, $testName);
	}

}
