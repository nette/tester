<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner\Output;

use Tester,
	Tester\Runner\Runner;


/**
 * File logger.
 *
 * @author     David Grudl
 */
class Logger implements Tester\Runner\OutputHandler
{
	/** @var Runner */
	private $runner;

	/** @var resource */
	private $file;


	public function __construct(Runner $runner, $file)
	{
		$this->runner = $runner;
		$this->file = fopen($file, 'w');
	}


	public function begin()
	{
		fputs($this->file, 'PHP ' . $this->runner->getPhp()->getVersion()
			. ' | ' . $this->runner->getPhp()->getCommandLine()
			. " | {$this->runner->threadCount} threads\n\n");
	}


	public function result($testName, $result, $message)
	{
		$message = Tester\Dumper::removeColors(trim($message));
		$outputs = array(
			Runner::PASSED => "-- OK: $testName",
			Runner::SKIPPED => "-- Skipped: $testName\n   $message",
			Runner::FAILED => "-- FAILED: $testName" . str_replace("\n", "\n   ", "\n" . $message),
		);
		fputs($this->file, $outputs[$result] . "\n\n");
	}


	public function end()
	{
		$jobCount = $this->runner->getJobCount();
		$results = $this->runner->getResults();
		$count = array_sum($results);
		fputs($this->file,
			($results[Runner::FAILED] ? 'FAILURES!' : 'OK')
			. " ($jobCount tests"
			. ($results[Runner::FAILED] ? ", {$results[Runner::FAILED]} failures" : '')
			. ($results[Runner::SKIPPED] ? ", {$results[Runner::SKIPPED]} skipped" : '')
			. ($jobCount !== $count ? ', ' . ($jobCount - $count) . ' not run' : '')
			. ')'
		);
	}

}
