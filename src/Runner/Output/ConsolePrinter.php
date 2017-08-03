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
	private $displaySkipped = false;

	/** @var resource */
	private $file;

	/** @var string */
	private $buffer;

	/** @var float */
	private $time;

	/** @var int */
	private $count;

	/** @var array */
	private $results;

	/** @var string */
	private $baseDir;


	public function __construct(Runner $runner, $displaySkipped = false, $file = 'php://output')
	{
		$this->runner = $runner;
		$this->displaySkipped = $displaySkipped;
		$this->file = fopen($file, 'w');
	}


	public function begin()
	{
		$this->count = 0;
		$this->baseDir = null;
		$this->results = [
			Test::PASSED => 0,
			Test::SKIPPED => 0,
			Test::FAILED => 0,
		];
		$this->time = -microtime(true);
		fwrite($this->file, $this->runner->getInterpreter()->getShortInfo()
			. ' | ' . $this->runner->getInterpreter()->getCommandLine()
			. " | {$this->runner->threadCount} thread" . ($this->runner->threadCount > 1 ? 's' : '') . "\n\n");
	}


	public function prepare(Test $test)
	{
		if ($this->baseDir === null) {
			$this->baseDir = dirname($test->getFile()) . DIRECTORY_SEPARATOR;
		} elseif (strpos($test->getFile(), $this->baseDir) !== 0) {
			$common = array_intersect_assoc(
				explode(DIRECTORY_SEPARATOR, $this->baseDir),
				explode(DIRECTORY_SEPARATOR, $test->getFile())
			);
			$this->baseDir = '';
			$prev = 0;
			foreach ($common as $i => $part) {
				if ($i !== $prev++) {
					break;
				}
				$this->baseDir .= $part . DIRECTORY_SEPARATOR;
			}
		}

		$this->count++;
	}


	public function finish(Test $test)
	{
		$this->results[$test->getResult()]++;
		$outputs = [
			Test::PASSED => '.',
			Test::SKIPPED => 's',
			Test::FAILED => Dumper::color('white/red', 'F'),
		];
		fwrite($this->file, $outputs[$test->getResult()]);

		$title = ($test->title ? "$test->title | " : '') . substr($test->getSignature(), strlen($this->baseDir));
		$message = '   ' . str_replace("\n", "\n   ", trim($test->message)) . "\n\n";
		if ($test->getResult() === Test::FAILED) {
			$this->buffer .= Dumper::color('red', "-- FAILED: $title") . "\n$message";
		} elseif ($test->getResult() === Test::SKIPPED && $this->displaySkipped) {
			$this->buffer .= "-- Skipped: $title\n$message";
		}
	}


	public function end()
	{
		$run = array_sum($this->results);
		fwrite($this->file, !$this->count ? "No tests found\n" :
			"\n\n" . $this->buffer . "\n"
			. ($this->results[Test::FAILED] ? Dumper::color('white/red') . 'FAILURES!' : Dumper::color('white/green') . 'OK')
			. " ($this->count test" . ($this->count > 1 ? 's' : '') . ', '
			. ($this->results[Test::FAILED] ? $this->results[Test::FAILED] . ' failure' . ($this->results[Test::FAILED] > 1 ? 's' : '') . ', ' : '')
			. ($this->results[Test::SKIPPED] ? $this->results[Test::SKIPPED] . ' skipped, ' : '')
			. ($this->count !== $run ? ($this->count - $run) . ' not run, ' : '')
			. sprintf('%0.1f', $this->time + microtime(true)) . ' seconds)' . Dumper::color() . "\n");

		$this->buffer = null;
	}
}
