<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner\Output;

use Tester;
use Tester\Runner\Test;


/**
 * JUnit xml format printer.
 */
class JUnitPrinter implements Tester\Runner\OutputHandler
{
	/** @var resource */
	private $file;

	/** @var string */
	private $buffer;

	/** @var float */
	private $startTime;

	/** @var array */
	private $results;


	public function __construct($file = 'php://output')
	{
		$this->file = fopen($file, 'w');
	}


	public function begin()
	{
		$this->results = [
			Test::PASSED => 0,
			Test::SKIPPED => 0,
			Test::FAILED => 0,
		];
		$this->startTime = microtime(true);
		fwrite($this->file, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<testsuites>\n");
	}


	public function prepare(Test $test)
	{
	}


	public function finish(Test $test)
	{
		$this->results[$test->getResult()]++;
		$this->buffer .= "\t\t<testcase classname=\"" . htmlspecialchars($test->getSignature()) . '" name="' . htmlspecialchars($test->getSignature()) . '"';

		switch ($test->getResult()) {
			case Test::FAILED:
				$this->buffer .= ">\n\t\t\t<failure message=\"" . htmlspecialchars($test->message) . "\"/>\n\t\t</testcase>\n";
				break;
			case Test::SKIPPED:
				$this->buffer .= ">\n\t\t\t<skipped/>\n\t\t</testcase>\n";
				break;
			case Test::PASSED:
				$this->buffer .= "/>\n";
				break;
		}
	}


	public function end()
	{
		$time = sprintf('%0.1f', microtime(true) - $this->startTime);
		$output = $this->buffer;
		$this->buffer = "\t<testsuite errors=\"{$this->results[Test::FAILED]}\" skipped=\"{$this->results[Test::SKIPPED]}\" tests=\"" . array_sum($this->results) . "\" time=\"$time\" timestamp=\"" . @date('Y-m-d\TH:i:s') . "\">\n";
		$this->buffer .= $output;
		$this->buffer .= "\t</testsuite>";

		fwrite($this->file, $this->buffer . "\n</testsuites>\n");
	}
}
