<?php declare(strict_types=1);

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester\Helpers;
use function count, in_array;


/**
 * Wraps a PHP executable and its resolved version, extensions, and command-line options.
 */
class PhpInterpreter
{
	private array $commandLine;
	private bool $cgi;
	private \stdClass $info;
	private string $error;


	/** @param string[]  $args */
	public function __construct(string $path, array $args = [])
	{
		$proc = @proc_open( // @ is escalated to exception
			[$path, '--version'],
			[['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
			$pipes,
		);
		if ($proc === false) {
			throw new \Exception("Cannot run PHP interpreter $path. Use -p option.");
		}

		fclose($pipes[0]);
		$output = stream_get_contents($pipes[1]);
		proc_close($proc);

		$this->commandLine = array_merge([$path], $args);
		if (str_contains($output, 'phpdbg')) {
			array_push($this->commandLine, '-qrrb', '-S', 'cli');
		}

		$proc = proc_open(
			array_merge(
				$this->commandLine,
				[
					'-d', 'register_argc_argv=on',
					__DIR__ . '/info.php',
					'serialized',
				],
			),
			[['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
			$pipes,
		) ?: throw new \Exception("Unable to run $path.");

		$output = stream_get_contents($pipes[1]);
		$this->error = trim(stream_get_contents($pipes[2]));
		if (proc_close($proc)) {
			throw new \Exception("Unable to run $path: " . preg_replace('#[\r\n ]+#', ' ', $this->error));
		}

		$parts = explode("\r\n\r\n", $output, 2);
		$this->cgi = count($parts) === 2;
		$output = $parts[(int) $this->cgi];
		$pos = strpos($output, 'O:8:"stdClass"');
		$info = $pos === false ? false : @unserialize(substr($output, $pos));
		if (!$info) {
			throw new \Exception("Unable to detect PHP version (output: $output).");
		}

		$this->info = $info;
		$this->error .= substr($output, 0, $pos);
		if ($this->cgi && $this->error) {
			$this->error .= "\n(note that PHP CLI generates better error messages)";
		}
	}


	/**
	 * Returns a new instance with additional command-line arguments appended.
	 * @param string[]  $args
	 */
	public function withArguments(array $args): static
	{
		$me = clone $this;
		array_push($me->commandLine, ...$args);
		return $me;
	}


	/**
	 * Returns a new instance with a -d INI option appended to the command line.
	 */
	public function withPhpIniOption(string $name, ?string $value = null): static
	{
		return $this->withArguments(['-d', $name . ($value === null ? '' : "=$value")]);
	}


	public function getCommandLine(): array
	{
		return $this->commandLine;
	}


	public function getCommandLineStr(): string
	{
		return implode(' ', array_map([Helpers::class, 'escapeArg'], $this->commandLine));
	}


	public function getVersion(): string
	{
		return $this->info->version;
	}


	/** @return array<array{string, string}>  [engine name, version] */
	public function getCodeCoverageEngines(): array
	{
		return $this->info->codeCoverageEngines;
	}


	public function isCgi(): bool
	{
		return $this->cgi;
	}


	public function getStartupError(): string
	{
		return $this->error;
	}


	public function getShortInfo(): string
	{
		return "PHP {$this->info->version} ({$this->info->sapi})"
			. ($this->info->phpDbgVersion ? "; PHPDBG {$this->info->phpDbgVersion}" : '');
	}


	public function hasExtension(string $name): bool
	{
		return in_array(strtolower($name), array_map('strtolower', $this->info->extensions), strict: true);
	}
}
