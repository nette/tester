<?php

/**
 * This file is part of the Nette Tester.
 *
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
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
			. " | {$this->runner->jobCount} threads\n\n");
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
		$results = $this->runner->getResults();
		fputs($this->file,
			($results[Runner::FAILED] ? 'FAILURES!' : 'OK')
			. ' (' . array_sum($results) . ' tests'
			. ($results[Runner::FAILED] ? ", {$results[Runner::FAILED]} failures" : '')
			. ($results[Runner::SKIPPED] ? ", {$results[Runner::SKIPPED]} skipped" : '')
			. ')'
		);
	}

}
