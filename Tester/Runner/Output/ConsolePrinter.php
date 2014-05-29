<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner\Output;

use Tester,
	Tester\Runner\Runner;


/**
 * Console printer.
 */
class ConsolePrinter implements Tester\Runner\OutputHandler
{
	/** @var Runner */
	private $runner;

	/** @var bool  display skipped tests information? */
	private $displaySkipped = FALSE;

	/** @var string */
	private $buffer;

	/** @var float */
	private $time;


	public function __construct(Runner $runner, $displaySkipped = FALSE)
	{
		$this->runner = $runner;
		$this->displaySkipped = $displaySkipped;
	}


	public function begin()
	{
		$this->time = -microtime(TRUE);
		echo 'PHP ' . $this->runner->getPhp()->getVersion()
			. ' | ' . $this->runner->getPhp()->getCommandLine()
			. " | {$this->runner->threadCount} thread" . ($this->runner->threadCount > 1 ? 's' : '') . "\n\n";
	}


	public function result($testName, $result, $message)
	{
		$outputs = array(
			Runner::PASSED => '.',
			Runner::SKIPPED => 's',
			Runner::FAILED => "\033[1;41;37mF\033[0m",
		);
		echo $outputs[$result];

		$message = '   ' . str_replace("\n", "\n   ", trim($message)) . "\n\n";
		if ($result === Runner::FAILED) {
			$this->buffer .= "\033[1;31m-- FAILED: $testName\033[0m\n$message";
		} elseif ($result === Runner::SKIPPED && $this->displaySkipped) {
			$this->buffer .= "-- Skipped: $testName\n$message";
		}
	}


	public function end()
	{
		$jobCount = $this->runner->getJobCount();
		$results = $this->runner->getResults();
		$count = array_sum($results);
		echo !$jobCount ? "No tests found\n" :
			"\n\n" . $this->buffer . "\n"
			. ($results[Runner::FAILED] ? "\033[1;41;37mFAILURES!" : "\033[1;42;37mOK")
			. " ($jobCount test" . ($jobCount > 1 ? 's' : '') . ", "
			. ($results[Runner::FAILED] ? $results[Runner::FAILED] . ' failure' . ($results[Runner::FAILED] > 1 ? 's' : '') . ', ' : '')
			. ($results[Runner::SKIPPED] ? $results[Runner::SKIPPED] . ' skipped, ' : '')
			. ($jobCount !== $count ? ($jobCount - $count) . ' not run, ' : '')
			. sprintf('%0.1f', $this->time + microtime(TRUE)) . " seconds)\033[0m\n";

		$this->buffer = NULL;
	}

}
