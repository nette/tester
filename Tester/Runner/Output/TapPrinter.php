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
 * Test Anything Protocol, http://testanything.org
 *
 * @author     David Grudl
 */
class TapPrinter implements Tester\Runner\OutputHandler
{
	/** @var Runner */
	private $runner;


	public function __construct(Runner $runner)
	{
		$this->runner = $runner;
	}


	public function begin()
	{
		echo "TAP version 13\n";
	}


	public function result($testName, $result, $message)
	{
		$message = Tester\Dumper::removeColors(trim($message));
		$outputs = array(
			Runner::PASSED => "ok $testName",
			Runner::SKIPPED => "ok $testName #skip $message",
			Runner::FAILED => "not ok $testName" . str_replace("\n", "\n# ", "\n" . $message),
		);
		echo $outputs[$result] . "\n";
	}


	public function end()
	{
		echo '1..' . array_sum($this->runner->getResults());
	}

}
