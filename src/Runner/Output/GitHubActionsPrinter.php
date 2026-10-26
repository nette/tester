<?php declare(strict_types=1);

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner\Output;

use Tester;
use Tester\Ansi;
use Tester\Runner\Test;
use function strlen;


/**
 * Emits GitHub Actions workflow commands (annotations) for failed tests,
 * so failures are shown directly in the pull request diff view.
 */
class GitHubActionsPrinter implements Tester\Runner\OutputHandler
{
	/** @var resource */
	private $file;


	public function __construct(?string $file = null)
	{
		$this->file = fopen($file ?? 'php://output', 'w') ?: throw new \RuntimeException("Cannot open file '$file' for writing.");
	}


	public function begin(): void
	{
	}


	public function prepare(Test $test): void
	{
	}


	public function finish(Test $test): void
	{
		if ($test->getResult() !== Test::Failed) {
			return;
		}

		$message = trim(Ansi::stripAnsi((string) $test->message));

		$file = strtr($test->getFile(), '\\', '/');
		if (($workspace = getenv('GITHUB_WORKSPACE')) !== false && $workspace !== '') {
			$workspace = rtrim(strtr($workspace, '\\', '/'), '/') . '/';
			if (str_starts_with($file, $workspace)) {
				$file = substr($file, strlen($workspace));
			}
		}

		$line = preg_match('#' . preg_quote(basename($test->getFile()), '#') . '\((\d+)\)#', $message, $m)
			? (int) $m[1]
			: null;

		fwrite($this->file, '::error file=' . self::escapeProperty($file)
			. ($line ? ",line=$line" : '')
			. ',title=' . self::escapeProperty($test->title ?: basename($test->getFile()))
			. '::' . self::escapeData($message) . "\n");
	}


	public function end(): void
	{
	}


	private static function escapeData(string $s): string
	{
		return strtr($s, ['%' => '%25', "\r" => '%0D', "\n" => '%0A']);
	}


	private static function escapeProperty(string $s): string
	{
		return strtr($s, ['%' => '%25', "\r" => '%0D', "\n" => '%0A', ':' => '%3A', ',' => '%2C']);
	}
}
