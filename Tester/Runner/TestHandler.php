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
 * Default test behavior.
 *
 * @author     David Grudl
 */
class TestHandler
{
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
		$options = Tester\Helpers::parseDocComment(file_get_contents($file));
		$options['name'] = $name = (isset($options[0]) ? preg_replace('#^TEST:\s*#i', '', $options[0]) . ' | ' : '')
			. implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $file), -3));
		$range = array(NULL);

		if (isset($options['skip'])) {
			return $this->runner->writeResult($name, Runner::SKIPPED, $options['skip']);

		} elseif (isset($options['phpversion'])) {
			foreach ((array) $options['phpversion'] as $phpVersion) {
				if (preg_match('#^(<=|<|==|=|!=|<>|>=|>)?\s*(.+)#', $phpVersion, $matches)
					&& version_compare($matches[2], $this->runner->getPhp()->getVersion(), $matches[1] ?: '>='))
				{
					return $this->runner->writeResult($name, Runner::SKIPPED, "Requires PHP $phpVersion.");
				}
			}
		}

		if (isset($options['dataprovider']) && preg_match('#^(\??)\s*([^,]+)\s*,?\s*(\S.*)?()#', $options['dataprovider'], $matches)) {
			try {
				$range = array_keys(Tester\DataProvider::load(dirname($file) . '/' . $matches[2], $matches[3]));
			} catch (\Exception $e) {
				return $this->runner->writeResult($name, $matches[1] ? Runner::SKIPPED : Runner::FAILED, $e->getMessage());
			}

		} elseif (isset($options['multiple'])) {
			$range = range(0, $options['multiple'] - 1);

		} elseif (isset($options['testcase']) && preg_match_all('#\sfunction\s+(test\w+)\(#', file_get_contents($file), $matches)) {
			$range = $matches[1];
		}

		$php = clone $this->runner->getPhp();
		if (isset($options['phpini'])) {
			foreach ((array) $options['phpini'] as $item) {
				$php->arguments .= ' -d ' . escapeshellarg(trim($item));
			}
		}

		foreach ($range as $item) {
			$this->runner->addJob($job = new Job($file, $php, $item === NULL ? NULL : escapeshellarg($item)));
			$job->options = $options;
			$job->options['name'] .= $item ? " [$item]" : '';
		}
	}


	/**
	 * @return void
	 */
	public function assess(Job $job)
	{
		$options = $job->options;
		$name = $options['name'];

		if ($job->getExitCode() === Job::CODE_SKIP) {
			$lines = explode("\n", trim($job->getOutput()));
			return $this->runner->writeResult($name, Runner::SKIPPED, end($lines));
		}

		$expected = isset($options['exitcode']) ? (int) $options['exitcode'] : Job::CODE_OK;
		if ($job->getExitCode() !== $expected) {
			return $this->runner->writeResult($name, Runner::FAILED, ($job->getExitCode() !== Job::CODE_FAIL ? "Exited with error code {$job->getExitCode()} (expected $expected)\n" : '') . $job->getOutput());
		}

		if ($this->runner->getPhp()->isCgi()) {
			$headers = $job->getHeaders();
			$code = isset($headers['Status']) ? (int) $headers['Status'] : 200;
			$expected = isset($options['httpcode']) ? (int) $options['httpcode'] : (isset($options['assertcode']) ? (int) $options['assertcode'] : $code);
			if ($expected && $code !== $expected) {
				return $this->runner->writeResult($name, Runner::FAILED, "Exited with HTTP code $code (expected $expected})");
			}
		}

		if (isset($options['outputmatchfile'])) {
			$file = dirname($job->getFile()) . '/' . $options['outputmatchfile'];
			if (!is_file($file)) {
				return $this->runner->writeResult($name, Runner::FAILED, "Missing matching file '$file'.");
			}
			$options['outputmatch'] = file_get_contents($file);
		} elseif (isset($options['outputmatch']) && !is_string($options['outputmatch'])) {
			$options['outputmatch'] = '';
		}

		if (isset($options['outputmatch']) && !Tester\Assert::isMatching($options['outputmatch'], $job->getOutput())) {
			Tester\Helpers::dumpOutput($job->getFile(), $job->getOutput(), '.actual');
			Tester\Helpers::dumpOutput($job->getFile(), $options['outputmatch'], '.expected');
			return $this->runner->writeResult($name, Runner::FAILED, 'Failed: output should match ' . Tester\Dumper::toLine($options['outputmatch']));
		}

		return $this->runner->writeResult($name, Runner::PASSED);
	}

}
