<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner\Output;

use Tester;
use Tester\Dumper;
use Tester\Runner\Runner;
use Tester\Runner\Test;


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
		echo $this->runner->getInterpreter()->getShortInfo()
			. ' | ' . $this->runner->getInterpreter()->getCommandLine()
			. " | {$this->runner->threadCount} thread" . ($this->runner->threadCount > 1 ? 's' : '') . "\n\n";
	}


	public function result($testName, $result, $message)
	{
		$outputs = [
			Test::PASSED => '.',
			Test::SKIPPED => 's',
			Test::FAILED => Dumper::color('white/red', 'F'),
		];
		echo $outputs[$result];

		$message = '   ' . str_replace("\n", "\n   ", trim($message)) . "\n\n";
		if ($result === Test::FAILED) {
			$this->buffer .= Dumper::color('red', "-- FAILED: $testName") . "\n$message";
		} elseif ($result === Test::SKIPPED && $this->displaySkipped) {
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
			. ($results[Test::FAILED] ? Dumper::color('white/red') . 'FAILURES!' : Dumper::color('white/green') . 'OK')
			. " ($jobCount test" . ($jobCount > 1 ? 's' : '') . ", "
			. ($results[Test::FAILED] ? $results[Test::FAILED] . ' failure' . ($results[Test::FAILED] > 1 ? 's' : '') . ', ' : '')
			. ($results[Test::SKIPPED] ? $results[Test::SKIPPED] . ' skipped, ' : '')
			. ($jobCount !== $count ? ($jobCount - $count) . ' not run, ' : '')
			. sprintf('%0.1f', $this->time + microtime(TRUE)) . ' seconds)' . Dumper::color() . "\n";

		$this->buffer = NULL;
	}

}
