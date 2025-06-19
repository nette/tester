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
use function sprintf, strlen;
use const DIRECTORY_SEPARATOR;


/**
 * Console printer.
 */
class ConsolePrinter implements Tester\Runner\OutputHandler
{
	private Runner $runner;

	/** @var resource */
	private $file;
	private bool $displaySkipped = false;
	private string $buffer;
	private float $time;
	private int $count;
	private array $results;
	private ?string $baseDir;
	private array $symbols;


	public function __construct(
		Runner $runner,
		bool $displaySkipped = false,
		?string $file = null,
		bool $ciderMode = false,
		private bool $lineMode = false,
	) {
		$this->runner = $runner;
		$this->displaySkipped = $displaySkipped;
		$this->file = fopen($file ?: 'php://output', 'w');
		$this->symbols = match (true) {
			$ciderMode => [Test::Passed => '🍏', Test::Skipped => 's', Test::Failed => '🍎'],
			$lineMode => [Test::Passed => Dumper::color('lime', 'OK'), Test::Skipped => Dumper::color('yellow', 'SKIP'), Test::Failed => Dumper::color('white/red', 'FAIL')],
			default => [Test::Passed => '.', Test::Skipped => 's', Test::Failed => Dumper::color('white/red', 'F')],
		};
	}


	public function begin(): void
	{
		$this->count = 0;
		$this->buffer = '';
		$this->baseDir = null;
		$this->results = [
			Test::Passed => 0,
			Test::Skipped => 0,
			Test::Failed => 0,
		];
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
		fwrite(
			$this->file,
			$this->lineMode
				? $this->generateFinishLine($test)
				: $this->symbols[$test->getResult()],
		);

		$title = ($test->title ? "$test->title | " : '') . substr($test->getSignature(), strlen($this->baseDir));
		$message = '   ' . str_replace("\n", "\n   ", trim((string) $test->message)) . "\n\n";
		$message = preg_replace('/^   $/m', '', $message);
		if ($test->getResult() === Test::Failed) {
			$this->buffer .= Dumper::color('red', "-- FAILED: $title") . "\n$message";
		} elseif ($test->getResult() === Test::Skipped && $this->displaySkipped) {
			$this->buffer .= "-- Skipped: $title\n$message";
		}
	}


	public function end(): void
	{
		$run = array_sum($this->results);
		fwrite($this->file, !$this->count ? "No tests found\n" :
			"\n\n" . $this->buffer . "\n"
			. ($this->results[Test::Failed] ? Dumper::color('white/red') . 'FAILURES!' : Dumper::color('white/green') . 'OK')
			. " ($this->count test" . ($this->count > 1 ? 's' : '') . ', '
			. ($this->results[Test::Failed] ? $this->results[Test::Failed] . ' failure' . ($this->results[Test::Failed] > 1 ? 's' : '') . ', ' : '')
			. ($this->results[Test::Skipped] ? $this->results[Test::Skipped] . ' skipped, ' : '')
			. ($this->count !== $run ? ($this->count - $run) . ' not run, ' : '')
			. sprintf('%0.1f', $this->time + microtime(true)) . ' seconds)' . Dumper::color() . "\n");

		$this->buffer = '';
	}


	private function generateFinishLine(Test $test): string
	{
		$result = $test->getResult();

		$shortFilePath = str_replace($this->baseDir, '', $test->getFile());
		$shortDirPath = dirname($shortFilePath) . DIRECTORY_SEPARATOR;
		$basename = basename($shortFilePath);
		$fileText = $result === Test::Failed
			? Dumper::color('red', $shortDirPath) . Dumper::color('white/red', $basename)
			: Dumper::color('gray', $shortDirPath) . Dumper::color('silver', $basename);

		$header = '· ';
		$titleText = $test->title
			? Dumper::color('fuchsia', " [$test->title]")
			: '';

		// failed tests messages will be printed after all tests are finished
		$message = '';
		if ($result !== Test::Failed && $test->message) {
			$indent = str_repeat(' ', mb_strlen($header));
			$message = preg_match('#\n#', $test->message)
				? "\n$indent" . preg_replace('#\r?\n#', '\0' . $indent, $test->message)
				: Dumper::color('olive', "[$test->message]");
		}

		return sprintf(
			"%s%s/%s %s%s %s %s %s\n",
			$header,
			array_sum($this->results),
			$this->count,
			$fileText,
			$titleText,
			$this->symbols[$result],
			Dumper::color('gray', sprintf('in %.2f s', $test->getDuration())),
			$message,
		);
	}
}
