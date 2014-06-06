<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner\Output;

use Tester,
	Tester\Runner\Runner;


/**
 * Verbose logger.
 */
class Logger implements Tester\Runner\OutputHandler
{
	/** @var Runner */
	private $runner;


	public function __construct(Runner $runner)
	{
		$this->runner = $runner;
	}


	public function begin()
	{
		echo 'PHP ' . $this->runner->getPhp()->getVersion()
			. ' | ' . $this->runner->getPhp()->getCommandLine()
			. " | {$this->runner->threadCount} threads\n\n";
	}


	public function result($testName, $result, $message)
	{
		$message = '   ' . str_replace("\n", "\n   ", Tester\Dumper::removeColors(trim($message)));
		$outputs = array(
			Runner::PASSED => "-- OK: $testName",
			Runner::SKIPPED => "-- Skipped: $testName\n$message",
			Runner::FAILED => "-- FAILED: $testName\n$message",
		);
		echo $outputs[$result] . "\n\n";
	}


	public function end()
	{
		$jobCount = $this->runner->getJobCount();
		$results = $this->runner->getResults();
		$count = array_sum($results);
		echo ($results[Runner::FAILED] ? 'FAILURES!' : 'OK')
			. " ($jobCount tests"
			. ($results[Runner::FAILED] ? ", {$results[Runner::FAILED]} failures" : '')
			. ($results[Runner::SKIPPED] ? ", {$results[Runner::SKIPPED]} skipped" : '')
			. ($jobCount !== $count ? ', ' . ($jobCount - $count) . ' not run' : '')
			. ')';
	}

}
