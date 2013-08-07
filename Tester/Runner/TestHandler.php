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

use Tester,
	Tester\Dumper;


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

	/** @var string[] */
	private $initiators, $assessments;


	public function __construct(Runner $runner)
	{
		$this->runner = $runner;
		foreach (get_class_methods($this) as $method) {
			if (preg_match('#^(assess|initiate)(.+)#', $method, $m)) {
				$this->{$m[1][0] === 'a' ? 'assessments' : 'initiators'}[strtolower($m[2])] = $method;
			}
		}
	}


	/**
	 * @return void
	 */
	public function initiate($file)
	{
		list($options, $testName) = $this->getAnnotations($file);
		$php = clone $this->runner->getPhp();
		$job = FALSE;

		foreach (array_intersect_key($this->initiators, $options) as $name => $method) {
			$res = $this->$method($options[$name], $php, $file);
			if ($res === TRUE) {
				$job = TRUE;
			} elseif ($res) {
				return $this->runner->writeResult($testName, $res[0], $res[1]);
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
		list($options, $testName) = $this->getAnnotations($job->getFile(), 'access');
		$testName .= ($job->getArguments() ? " [{$job->getArguments()}]" : '');
		$options += array(
			'exitcode' => Job::CODE_OK,
			'httpcode' => self::HTTP_OK,
		);

		foreach (array_intersect_key($this->assessments, $options) as $name => $method) {
			if ($res = $this->$method($job, $options[$name])) {
				return $this->runner->writeResult($testName, $res[0], $res[1]);
			}
		}
		$this->runner->writeResult($testName, Runner::PASSED);
	}


	private function initiateSkip($message)
	{
		return array(Runner::SKIPPED, $message);
	}


	private function initiatePhpVersion($versions, PhpExecutable $php)
	{
		foreach ((array) $versions as $version) {
			if (preg_match('#^(<=|<|==|=|!=|<>|>=|>)?\s*(.+)#', $version, $matches)
				&& version_compare($matches[2], $php->getVersion(), $matches[1] ?: '>='))
			{
				return array(Runner::SKIPPED, "Requires PHP $version.");
			}
		}
	}


	private function initiatePhpIni($values, PhpExecutable $php)
	{
		foreach ((array) $values as $item) {
			$php->arguments .= ' -d ' . escapeshellarg(trim($item));
		}
	}


	private function initiateDataProvider($provider, PhpExecutable $php, $file)
	{
		if (!preg_match('#^(\??)\s*([^,]+)\s*,?\s*(\S.*)?()#', $provider, $matches)) {
			return array(Runner::FAILED, 'Invalid @dataprovider value.');
		}
		try {
			foreach (array_keys(Tester\DataProvider::load(dirname($file) . DIRECTORY_SEPARATOR . $matches[2], $matches[3])) as $item) {
				$this->runner->addJob(new Job($file, $php, escapeshellarg($item)));
			}
		} catch (\Exception $e) {
			return array($matches[1] ? Runner::SKIPPED : Runner::FAILED, $e->getMessage());
		}
		return TRUE;
	}


	private function initiateMultiple($count, PhpExecutable $php, $file)
	{
		foreach (range(0, (int) $count - 1) as $arg) {
			$this->runner->addJob(new Job($file, $php, (string) $arg));
		}
		return TRUE;
	}


	private function initiateTestCase($foo, PhpExecutable $php, $file)
	{
		if (preg_match_all('#\sfunction\s+(test\w+)\(#', file_get_contents($file), $matches)) {
			foreach ($matches[1] as $item) {
				$this->runner->addJob(new Job($file, $php, escapeshellarg($item)));
			}
			return TRUE;
		}
	}


	private function assessExitCode(Job $job, $code)
	{
		$code = (int) $code;
		if ($job->getExitCode() === Job::CODE_SKIP) {
			$lines = explode("\n", trim($job->getOutput()));
			return array(Runner::SKIPPED, end($lines));

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
		$options = Tester\Helpers::parseDocComment(file_get_contents($file));
		$testName = (isset($options[0]) ? preg_replace('#^TEST:\s*#i', '', $options[0]) . ' | ' : '')
			. implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $file), -3));
		return array($options, $testName);
	}

}
