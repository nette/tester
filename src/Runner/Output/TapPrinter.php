<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner\Output;

use Tester;
use Tester\Runner\Runner;


/**
 * Test Anything Protocol, http://testanything.org
 */
class TapPrinter implements Tester\Runner\OutputHandler
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
		fwrite($this->file, "TAP version 13\n");
	}


	public function result($testName, $result, $message)
	{
		$message = str_replace("\n", "\n# ", trim($message));
		$outputs = array(
			Runner::PASSED => "ok $testName",
			Runner::SKIPPED => "ok $testName #skip $message",
			Runner::FAILED => "not ok $testName\n# $message",
		);
		fwrite($this->file, $outputs[$result] . "\n");
	}


	public function end()
	{
		fwrite($this->file, '1..' . array_sum($this->runner->getResults()));
	}

}
