<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

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
	/** @var resource */
	private $file;

	/** @var list<string> */
	private array $buffer;

	/**
	 * @phpstan-var array<Alias_TestResultState, string>
	 * @var array<int, string>
	 */
	private array $symbols;

	/**
	 * @phpstan-var array<Alias_TestResultState, int>
	 * @var array<int, string>
	 */
	private array $results = [
		Test::Passed => 0,
		Test::Skipped => 0,
		Test::Failed => 0,
	];

	private float $time;
	private int $count;
	private ?string $baseDir;
	private int $resultsCount = 0;

	/**
	 * @param bool $lineMode If `true`, reports each finished test on separate line.
	 */
	public function __construct(
		private Runner $runner,
		private bool $displaySkipped = false,
		?string $file = null,
		bool $ciderMode = false,
		private bool $lineMode = false,
	) {
		$this->runner = $runner;
		$this->displaySkipped = $displaySkipped;
		$this->file = fopen($file ?: 'php://output', 'w');

		$this->symbols = [
			Test::Passed => $this->lineMode ? Dumper::color('lime', 'OK') : '.',
			Test::Skipped => $this->lineMode ? Dumper::color('yellow', 'SKIP') : 's',
			Test::Failed => $this->lineMode ? Dumper::color('white/red', 'FAIL') : Dumper::color('white/red', 'F'),
		];

		if ($ciderMode) {
			$this->symbols[Test::Passed] = '🍏';
			$this->symbols[Test::Skipped] = '🍋';
			$this->symbols[Test::Failed] = '🍎';
		}
	}


	public function begin(): void
	{
		$this->count = 0;
		$this->buffer = [];
		$this->baseDir = null;
		$this->time = -microtime(true);
		fwrite($this->file, $this->runner->getInterpreter()->getShortInfo()
			. ' | ' . $this->runner->getInterpreter()->getCommandLine()
			. " | {$this->runner->threadCount} thread" . ($this->runner->threadCount > 1 ? 's' : '') . "\n\n");
	}


	public function prepare(Test $test): void
	{
		if ($this->baseDir === null) {
			$this->baseDir = dirname($test->getFile()) . DIRECTORY_SEPARATOR;
		} elseif (!str_starts_with($test->getFile(), $this->baseDir)) {
			$common = array_intersect_assoc(
				explode(DIRECTORY_SEPARATOR, $this->baseDir),
				explode(DIRECTORY_SEPARATOR, $test->getFile()),
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


	public function finish(Test $test): void
	{
		$this->results[$test->getResult()]++;
		$this->lineMode
			? $this->printFinishedTestLine($test)
			: $this->printFinishedTestDot($test);

		$title = ($test->title ? "$test->title | " : '') . substr($test->getSignature(), strlen($this->baseDir));
		$message = '   ' . str_replace("\n", "\n   ", trim((string) $test->message)) . "\n\n";
		$message = preg_replace('/^   $/m', '', $message);
		if ($test->getResult() === Test::Failed) {
			$this->buffer[] = Dumper::color('red', "-- FAILED: $title") . "\n$message";
		} elseif ($test->getResult() === Test::Skipped && $this->displaySkipped) {
			$this->buffer[] = "-- Skipped: $title\n$message";
		}
	}


	public function end(): void
	{
		$run = array_sum($this->results);
		fwrite($this->file, !$this->count ? "No tests found\n" :
			"\n\n" . implode('', $this->buffer) . "\n"
			. ($this->results[Test::Failed] ? Dumper::color('white/red') . 'FAILURES!' : Dumper::color('white/green') . 'OK')
			. " ($this->count test" . ($this->count > 1 ? 's' : '') . ', '
			. ($this->results[Test::Failed] ? $this->results[Test::Failed] . ' failure' . ($this->results[Test::Failed] > 1 ? 's' : '') . ', ' : '')
			. ($this->results[Test::Skipped] ? $this->results[Test::Skipped] . ' skipped, ' : '')
			. ($this->count !== $run ? ($this->count - $run) . ' not run, ' : '')
			. sprintf('%0.1f', $this->time + microtime(true)) . ' seconds)' . Dumper::color() . "\n");

		$this->buffer = [];
		$this->resultsCount = 0;
	}


	private function printFinishedTestDot(Test $test): void
	{
		fwrite($this->file, $this->symbols[$test->getResult()]);
	}


	private function printFinishedTestLine(Test $test): void
	{
		$this->resultsCount++;
		$result = $test->getResult();

		$shortFilePath = str_replace($this->baseDir, '', $test->getFile());
		$shortDirPath = dirname($shortFilePath) . DIRECTORY_SEPARATOR;
		$basename = basename($shortFilePath);

		// Filename.
		if ($result === Test::Failed) {
			$fileText =	Dumper::color('red', $shortDirPath) . Dumper::color('white/red', $basename);
		} else {
			$fileText =	Dumper::color('gray', $shortDirPath) . Dumper::color('silver', $basename);
		}

		// Line header.
		$header = "· ";
		// Test's title.
		$titleText = $test->title
			? Dumper::color('fuchsia', " [$test->title]")
			: '';

		// Print test's message only if it's not failed (those will be printed
		// after all tests are finished and will contain detailed info about the
		// failed test).
		$message = '';
		if ($result !== Test::Failed && $test->message) {
			$message = $test->message;
			$indent = str_repeat(' ', mb_strlen($header));

			if (preg_match('#\n#', $message)) {
				$message = "\n$indent" . preg_replace('#\r?\n#', '\0' . $indent, $message);
			} else {
				$message = Dumper::color('olive', "[$message]");
			}
		}

		$output = sprintf(
			"%s%s %s %s %s %s\n",
			$header,
			"{$this->resultsCount}/{$this->count}",
			"$fileText{$titleText}",
			$this->symbols[$result],
			Dumper::color('gray', sprintf("in %.2f s", $test->getDuration())),
			$message,
		);

		fwrite($this->file, $output);
	}
}
