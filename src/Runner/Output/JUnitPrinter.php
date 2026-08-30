<?php declare(strict_types=1);

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner\Output;

use Tester;
use Tester\Runner\Test;
use function sprintf;


/**
 * Outputs test results in JUnit XML format.
 */
class JUnitPrinter implements Tester\Runner\OutputHandler
{
	/** @var resource */
	private $file;
	private string $buffer;
	private float $startTime;

	/** @var array<int, int>  result type (Test::*) => count */
	private array $results;


	public function __construct(?string $file = null)
	{
		$this->file = fopen($file ?? 'php://output', 'w') ?: throw new \RuntimeException("Cannot open file '$file' for writing.");
	}


	public function begin(): void
	{
		$this->buffer = '';
		$this->results = [Test::Passed => 0, Test::Skipped => 0, Test::Failed => 0];
		$this->startTime = microtime(as_float: true);
		fwrite($this->file, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<testsuites>\n");
	}


	public function prepare(Test $test): void
	{
	}


	public function finish(Test $test): void
	{
		$signature = htmlspecialchars(self::sanitize($test->getSignature()));
		$this->results[$test->getResult()]++;
		$this->buffer .= "\t\t<testcase classname=\"" . $signature . '" name="' . $signature . '"';
		$this->buffer .= match ($test->getResult()) {
			Test::Failed => ">\n\t\t\t<failure message=\"" . htmlspecialchars(self::sanitize($test->message ?? ''), ENT_COMPAT | ENT_HTML5) . "\"/>\n\t\t</testcase>\n",
			Test::Skipped => ">\n\t\t\t<skipped/>\n\t\t</testcase>\n",
			Test::Passed => "/>\n",
		};
	}


	public function end(): void
	{
		$time = sprintf('%0.1f', microtime(as_float: true) - $this->startTime);
		$output = $this->buffer;
		$this->buffer = "\t<testsuite errors=\"{$this->results[Test::Failed]}\" skipped=\"{$this->results[Test::Skipped]}\" tests=\"" . array_sum($this->results) . "\" time=\"$time\" timestamp=\"" . @date('Y-m-d\TH:i:s') . "\">\n";
		$this->buffer .= $output;
		$this->buffer .= "\t</testsuite>";

		fwrite($this->file, $this->buffer . "\n</testsuites>\n");
	}


	/**
	 * Replaces invalid UTF-8 sequences and characters not allowed in XML 1.0, which may come from the test output.
	 */
	private static function sanitize(string $s): string
	{
		$s = htmlspecialchars_decode(htmlspecialchars($s, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'), ENT_NOQUOTES);
		return preg_replace('#[\x00-\x08\x0B\x0C\x0E-\x1F\x{FFFE}\x{FFFF}]#u', "\u{FFFD}", $s);
	}
}
