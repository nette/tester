<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner\Output;

use Tester;
use Tester\Runner\Runner;
use Tester\Runner\Test;


/**
 * Verbose logger.
 */
class Logger implements Tester\Runner\OutputHandler
{
	/** @var Runner */
	private $runner;

	/** @var resource */
	private $file;


	public function __construct(Runner $runner, $file = 'php://output')
	{
		$this->runner = $runner;
		$this->file = fopen($file, 'w');
	}


	public function begin()
	{
		fwrite($this->file, 'PHP ' . $this->runner->getInterpreter()->getVersion()
			. ' | ' . $this->runner->getInterpreter()->getCommandLine()
			. " | {$this->runner->threadCount} threads\n\n");
	}


	public function result($testName, $result, $message)
	{
		$message = '   ' . str_replace("\n", "\n   ", Tester\Dumper::removeColors(trim($message)));
		$outputs = [
			Test::PASSED => "-- OK: $testName",
			Test::SKIPPED => "-- Skipped: $testName\n$message",
			Test::FAILED => "-- FAILED: $testName\n$message",
		];
		fwrite($this->file, $outputs[$result] . "\n\n");
	}


	public function end()
	{
		$jobCount = $this->runner->getJobCount();
		$results = $this->runner->getResults();
		$count = array_sum($results);
		fwrite($this->file,
			($results[Test::FAILED] ? 'FAILURES!' : 'OK')
			. " ($jobCount tests"
			. ($results[Test::FAILED] ? ", {$results[Test::FAILED]} failures" : '')
			. ($results[Test::SKIPPED] ? ", {$results[Test::SKIPPED]} skipped" : '')
			. ($jobCount !== $count ? ', ' . ($jobCount - $count) . ' not run' : '')
			. ')'
		);
	}

}
