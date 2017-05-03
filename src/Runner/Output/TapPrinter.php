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


	public function result(Test $test)
	{
		$message = str_replace("\n", "\n# ", trim($test->message));
		$outputs = [
			Test::PASSED => "ok {$test->getName()}",
			Test::SKIPPED => "ok {$test->getName()} #skip $message",
			Test::FAILED => "not ok {$test->getName()}\n# $message",
		];
		fwrite($this->file, $outputs[$test->getResult()] . "\n");
	}


	public function end()
	{
		fwrite($this->file, '1..' . array_sum($this->runner->getResults()));
	}

}
