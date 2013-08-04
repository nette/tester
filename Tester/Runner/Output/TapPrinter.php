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
	Tester\AssertException,
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


	public function result($testName, $result, $message, \Exception $exception = NULL)
	{
		switch ($result) {
			case Runner::PASSED:
				echo "ok $testName";
				break;

			case Runner::SKIPPED:
				echo "ok $testName #skip $message";
				break;

			default:
				echo "not ok $testName";
				if ($exception instanceof AssertException) {
					echo "\n## message: " . self::toLine($exception->getMessage());
					echo "\n## actual: " . self::toLine(var_export($exception->actual, TRUE));
					echo "\n## expected: " . self::toLine(var_export($exception->expected, TRUE));
				}
				echo str_replace("\n", "\n# ", "\n" . trim($message));
		}
		echo "\n";
	}


	public function end()
	{
		echo '1..' . array_sum($this->runner->getResults());
	}


	private static function toLine($s)
	{
		return preg_replace_callback('#[\x00-\x08\x0a-\x1f\x7f-\xff\\\\]#', function($m) {
			return sprintf('\x%02x', ord($m[0]));
		}, $s);
	}

}
