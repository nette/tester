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
 *
 * @author     David Grudl
 */
class ConsolePrinter implements Tester\Runner\OutputHandler
{
	/** count of lines to print */
	const PRINT_LINES = 15;

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
			. " | {$this->runner->threadCount} threads\n\n";
	}


	public function result($testName, $result, $message)
	{
		$outputs = array(
			Runner::PASSED => '.',
			Runner::SKIPPED => 's',
			Runner::FAILED => "\033[1;41;37mF\033[0m",
		);
		echo $outputs[$result];

		if ($result === Runner::FAILED) {
			$lines = explode("\n", trim($message), self::PRINT_LINES + 1);
			$lines[self::PRINT_LINES] = isset($lines[self::PRINT_LINES]) ? '...' : '';
			$this->buffer .= "\033[1;31m-- FAILED: $testName\033[0m\n   " . implode("\n   ", $lines) . "\n";

		} elseif ($result === Runner::SKIPPED && $this->displaySkipped) {
			$this->buffer .= "-- Skipped: $testName\n   $message\n\n";
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
			. " ($jobCount tests, "
			. ($results[Runner::FAILED] ? $results[Runner::FAILED] . ' failures, ' : '')
			. ($results[Runner::SKIPPED] ? $results[Runner::SKIPPED] . ' skipped, ' : '')
			. ($jobCount !== $count ? ($jobCount - $count) . ' not run, ' : '')
			. sprintf('%0.1f', $this->time + microtime(TRUE)) . " seconds)\033[0m\n";

		$this->buffer = NULL;
	}

}
