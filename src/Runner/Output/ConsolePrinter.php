<?php declare(strict_types=1);

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner\Output;

use Tester;
use Tester\Ansi;
use Tester\Environment;
use Tester\Runner\Job;
use Tester\Runner\Runner;
use Tester\Runner\Test;
use function count, fwrite, sprintf, str_repeat, strlen;


/**
 * Console printer.
 */
class ConsolePrinter implements Tester\Runner\OutputHandler
{
	public const ModeDots = 1;
	public const ModeCider = 2;
	public const ModeLines = 3;

	private const MaxDisplayedThreads = 20;

	/** @var resource */
	private $file;
	private string $buffer;
	private float $time;
	private int $count;

	/** @var array<Test::Passed|Test::Skipped|Test::Failed, int>  result type => count */
	private array $results;
	private ?string $baseDir;
	private int $panelWidth = 60;
	private int $panelHeight = 0;

	/** @var \WeakMap<Job, float> */
	private \WeakMap $startTimes;


	public function __construct(
		private Runner $runner,
		private bool $displaySkipped = false,
		?string $file = null,
		/** @var self::ModeDots|self::ModeCider|self::ModeLines */
		private int $mode = self::ModeDots,
	) {
		$this->file = fopen($file ?? 'php://output', 'w') ?: throw new \RuntimeException("Cannot open file '$file' for writing.");
		$this->startTimes = new \WeakMap;
	}


	public function begin(): void
	{
		$this->count = 0;
		$this->buffer = '';
		$this->baseDir = null;
		$this->results = [Test::Passed => 0, Test::Skipped => 0, Test::Failed => 0];
		$this->time = -microtime(as_float: true);
		if ($this->mode === self::ModeCider && $this->runner->threadCount < 2) {
			$this->mode = self::ModeLines;
		}
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
		$result = $test->getResult();
		$this->results[$result]++;
		fwrite($this->file, match ($this->mode) {
			self::ModeDots => [Test::Passed => '.', Test::Skipped => 's', Test::Failed => Ansi::colorize('F', 'white/red')][$result],
			self::ModeCider => '',
			self::ModeLines => $this->generateFinishLine($test),
		});

		$title = ($test->title ? "$test->title | " : '') . substr($test->getSignature(), strlen((string) $this->baseDir));
		$message = '   ' . str_replace("\n", "\n   ", trim((string) $test->message)) . "\n\n";
		$message = preg_replace('/^   $/m', '', $message);
		if ($result === Test::Failed) {
			$this->buffer .= Ansi::colorize("-- FAILED: $title", 'red') . "\n$message";
		} elseif ($result === Test::Skipped && $this->displaySkipped) {
			$this->buffer .= "-- Skipped: $title\n$message";
		}
	}


	public function end(): void
	{
		if ($this->panelHeight) {
			fwrite($this->file, Ansi::cursorMove(y: -$this->panelHeight)
				. str_repeat(Ansi::clearLine() . "\n", $this->panelHeight)
				. Ansi::cursorMove(y: -$this->panelHeight));
		}

		$run = array_sum($this->results);
		fwrite($this->file, !$this->count ? "No tests found\n" :
			"\n\n" . $this->buffer . "\n"
			. ($this->results[Test::Failed] ? Ansi::color('white/red') . 'FAILURES!' : Ansi::color('white/green') . 'OK')
			. " ($this->count test" . ($this->count > 1 ? 's' : '') . ', '
			. ($this->results[Test::Failed] ? $this->results[Test::Failed] . ' failure' . ($this->results[Test::Failed] > 1 ? 's' : '') . ', ' : '')
			. ($this->results[Test::Skipped] ? $this->results[Test::Skipped] . ' skipped, ' : '')
			. ($this->count !== $run ? ($this->count - $run) . ' not run, ' : '')
			. sprintf('%0.1f', $this->time + microtime(as_float: true)) . ' seconds)' . Ansi::reset() . "\n");

		$this->buffer = '';
	}


	private function generateFinishLine(Test $test): string
	{
		$result = $test->getResult();
		$shortFilePath = str_replace((string) $this->baseDir, '', $test->getFile());
		$shortDirPath = dirname($shortFilePath) . DIRECTORY_SEPARATOR;
		$basename = basename($shortFilePath);
		$fileText = $result === Test::Failed
			? Ansi::colorize($shortDirPath, 'red') . Ansi::colorize($basename, 'white/red')
			: Ansi::colorize($shortDirPath, 'gray') . Ansi::colorize($basename, 'silver');

		$header = '· ';
		$titleText = $test->title
			? Ansi::colorize(" [$test->title]", 'fuchsia')
			: '';

		// failed tests messages will be printed after all tests are finished
		$message = '';
		if ($result !== Test::Failed && $test->message) {
			$indent = str_repeat(' ', Ansi::textWidth($header));
			$message = preg_match('#\n#', $test->message)
				? "\n$indent" . preg_replace('#\r?\n#', '\0' . $indent, $test->message)
				: Ansi::colorize("[$test->message]", 'olive');
		}

		return sprintf(
			"%s%s/%s %s%s %s %s %s\n",
			$header,
			array_sum($this->results),
			$this->count,
			$fileText,
			$titleText,
			match ($result) {
				Test::Passed => Ansi::colorize('OK', 'lime'), Test::Skipped => Ansi::colorize('SKIP', 'yellow'), Test::Failed => Ansi::colorize('FAIL', 'white/red')
			},
			Ansi::colorize(sprintf('in %.2f s', $test->getDuration()), 'gray'),
			$message,
		);
	}


	public function jobStarted(Job $job): void
	{
		$this->startTimes[$job] = microtime(true);
	}


	/**
	 * @param Job[]  $running
	 */
	public function tick(array $running): void
	{
		if ($this->mode !== self::ModeCider) {
			return;
		}

		// Move cursor up to overwrite previous output
		if ($this->panelHeight) {
			fwrite($this->file, Ansi::cursorMove(y: -$this->panelHeight));
		}

		$lines = [];

		// Header with progress bar
		$barWidth = $this->panelWidth - 12;
		$filled = (int) round($barWidth * ($this->runner->getFinishedCount() / $this->runner->getJobCount()));
		$lines[] = '╭' . Ansi::pad(' ' . str_repeat('█', $filled) . str_repeat('░', $barWidth - $filled) . ' ', $this->panelWidth - 2, '─', STR_PAD_BOTH) . '╮';

		$threadJobs = [];
		foreach ($running as $job) {
			$threadJobs[(int) $job->getEnvironmentVariable(Environment::VariableThread)] = $job;
		}

		// Thread lines
		$numWidth = strlen((string) $this->runner->threadCount);
		$displayCount = min($this->runner->threadCount, self::MaxDisplayedThreads);

		for ($t = 1; $t <= $displayCount; $t++) {
			if (isset($threadJobs[$t])) {
				$job = $threadJobs[$t];
				$name = basename($job->getTest()->getFile());
				$time = sprintf('%0.1fs', microtime(true) - ($this->startTimes[$job] ?? microtime(true)));
				$nameWidth = $this->panelWidth - $numWidth - strlen($time) - 7;
				$name = Ansi::pad(Ansi::truncate($name, $nameWidth), $nameWidth);
				$line = Ansi::colorize(sprintf("%{$numWidth}d:", $t), 'lime') . " $name " . Ansi::colorize($time, 'yellow');
			} else {
				$line = Ansi::pad(Ansi::colorize(sprintf("%{$numWidth}d: -", $t), 'gray'), $this->panelWidth - 4);
			}
			$lines[] = '│ ' . $line . ' │';
		}

		if ($this->runner->threadCount > self::MaxDisplayedThreads) {
			$more = $this->runner->threadCount - self::MaxDisplayedThreads;
			$ellipsis = Ansi::colorize("… and $more more", 'gray');
			$lines[] = '│' . Ansi::pad($ellipsis, $this->panelWidth - 2) . '│';
		}

		// Footer: (85 tests, 🍏×74 🍎×2, 9.0s)
		$summary = "($this->count tests, "
			. ($this->results[Test::Passed] ? "🍏×{$this->results[Test::Passed]}" : '')
			. ($this->results[Test::Failed] ? " 🍎×{$this->results[Test::Failed]}" : '')
			. ', ' . sprintf('%0.1fs', $this->time + microtime(true)) . ')';
		$lines[] = '╰' . Ansi::pad($summary, $this->panelWidth - 2, '─', STR_PAD_BOTH) . '╯';

		foreach ($lines as $line) {
			fwrite($this->file, "\r" . $line . Ansi::clearLine() . "\n");
		}
		fflush($this->file);

		$this->panelHeight = count($lines);
	}
}
