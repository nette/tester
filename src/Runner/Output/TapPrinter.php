<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester\Runner\Output;

use Tester;
use Tester\Runner\Test;


/**
 * Test Anything Protocol, http://testanything.org
 */
class TapPrinter implements Tester\Runner\OutputHandler
{
	/** @var resource */
	private $file;

	/** @var array */
	private $results;


	public function __construct(string $file = null)
	{
		$this->file = fopen($file ?: 'php://output', 'w');
	}


	public function begin(): void
	{
		$this->results = [
			Test::PASSED => 0,
			Test::SKIPPED => 0,
			Test::FAILED => 0,
		];
		fwrite($this->file, "TAP version 13\n");
	}


	public function prepare(Test $test): void
	{
	}


	public function finish(Test $test): void
	{
		$this->results[$test->getResult()]++;
		$message = str_replace("\n", "\n# ", trim((string) $test->message));
		$outputs = [
			Test::PASSED => "ok {$test->getSignature()}",
			Test::SKIPPED => "ok {$test->getSignature()} #skip $message",
			Test::FAILED => "not ok {$test->getSignature()}\n# $message",
		];
		fwrite($this->file, $outputs[$test->getResult()] . "\n");
	}


	public function end(): void
	{
		fwrite($this->file, '1..' . array_sum($this->results));
	}
}
