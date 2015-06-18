<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner\Output;

use Tester;
use Tester\Runner\Runner;


/**
 * JUnit xml format printer.
 */
class JUnitPrinter implements Tester\Runner\OutputHandler
{
	/** @var Runner */
	private $runner;

	/** @var resource */
	private $file;

	/** @var string */
	private $buffer;

	/** @var float */
	private $startTime;

	public function __construct(Runner $runner, $file = 'php://output')
	{
		$this->runner = $runner;
		$this->file = fopen($file, 'w');
	}


	public function begin()
	{
		$this->startTime = microtime(TRUE);
		fwrite($this->file, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<testsuites>\n");
	}


	public function result($testName, $result, $message)
	{
		$this->buffer .= "\t\t<testcase classname=\"" . htmlspecialchars($testName) . '" name="' . htmlspecialchars($testName) . '"';

		switch ($result) {
			case Runner::FAILED:
				$this->buffer .= ">\n\t\t\t<failure message=\"" . htmlspecialchars($message) . "\"/>\n\t\t</testcase>\n";
				break;
			case Runner::SKIPPED:
				$this->buffer .= ">\n\t\t\t<skipped/>\n\t\t</testcase>\n";
				break;
			case Runner::PASSED:
				$this->buffer .= "/>\n";
				break;
		}
	}


	public function end()
	{
		$time = sprintf('%0.1f', microtime(TRUE) - $this->startTime);
		$output = $this->buffer;
		$results = $this->runner->getResults();
		$this->buffer = "\t<testsuite errors=\"{$results[3]}\" skipped=\"{$results[2]}\" tests=\"" . array_sum($results) . "\" time=\"$time\" timestamp=\"" . date('Y-m-d\TH:i:s') . "\">\n";
		$this->buffer .= $output;
		$this->buffer .= "\t</testsuite>";

		fwrite($this->file, $this->buffer . "\n</testsuites>\n");
	}

}
