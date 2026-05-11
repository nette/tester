<?php declare(strict_types=1);

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester;

use function array_key_exists, count, in_array;
use const PHP_OUTPUT_HANDLER_FLUSHABLE, PHP_SAPI;


/**
 * Testing environment.
 */
class Environment
{
	/** Enable console colors (1 = yes, 0 = no) */
	public const VariableColors = 'NETTE_TESTER_COLORS';

	/** Set when the test is run by Runner */
	public const VariableRunner = 'NETTE_TESTER_RUNNER';

	/** Code coverage engine name */
	public const VariableCoverageEngine = 'NETTE_TESTER_COVERAGE_ENGINE';

	/** Path to the code coverage file */
	public const VariableCoverage = 'NETTE_TESTER_COVERAGE';

	/** Thread number in parallel execution */
	public const VariableThread = 'NETTE_TESTER_THREAD';

	/** @deprecated use Environment::VariableColors */
	public const COLORS = self::VariableColors;

	/** @deprecated use Environment::VariableRunner */
	public const RUNNER = self::VariableRunner;

	/** @deprecated use Environment::VariableCoverageEngine */
	public const COVERAGE_ENGINE = self::VariableCoverageEngine;

	/** @deprecated use Environment::VariableCoverage */
	public const COVERAGE = self::VariableCoverage;

	/** @deprecated use Environment::VariableThread */
	public const THREAD = self::VariableThread;

	public static bool $checkAssertions = false;
	public static bool $useColors = false;
	private static int $exitCode = 0;


	/**
	 * Sets up error handling, colors, code coverage, and assertion tracking for the test process.
	 */
	public static function setup(): void
	{
		self::setupErrors();
		self::setupColors();

		class_exists(Runner\Job::class);
		class_exists(Dumper::class);
		class_exists(Assert::class);

		$annotations = self::getTestAnnotations();
		self::$checkAssertions = !isset($annotations['outputmatch']) && !isset($annotations['outputmatchfile']);

		$coverageFile = getenv(self::VariableCoverage);
		$coverageEngine = getenv(self::VariableCoverageEngine);
		if ($coverageFile && $coverageEngine) {
			CodeCoverage\Collector::start($coverageFile, $coverageEngine);
		}

		if (getenv('TERMINAL_EMULATOR') === 'JetBrains-JediTerm') {
			Dumper::$maxPathSegments = -1;
			Dumper::$pathSeparator = '/';
		}
	}


	/**
	 * Detects whether ANSI colors should be used and wraps output buffer to strip them when not.
	 */
	public static function setupColors(): void
	{
		self::$useColors = getenv(self::VariableColors) !== false
			? (bool) getenv(self::VariableColors)
			: (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg')
				&& getenv('NO_COLOR') === false // https://no-color.org
				&& (getenv('FORCE_COLOR')
					|| (function_exists('sapi_windows_vt100_support')
						? sapi_windows_vt100_support(STDOUT)
						: @stream_isatty(STDOUT)) // @ may trigger error 'cannot cast a filtered stream on this system'
				);

		ob_start(
			fn(string $s): string => self::$useColors ? $s : Ansi::stripAnsi($s),
			1,
			PHP_OUTPUT_HANDLER_FLUSHABLE,
		);
	}


	/**
	 * Sets error_reporting, exception handler, and shutdown handler for clean test output.
	 */
	public static function setupErrors(): void
	{
		error_reporting(E_ALL);
		ini_set('display_errors', '1');
		ini_set('html_errors', '0');
		ini_set('log_errors', '0');

		set_exception_handler([self::class, 'handleException']);

		set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
			if (
				in_array($severity, [E_RECOVERABLE_ERROR, E_USER_ERROR], strict: true)
				|| ($severity & error_reporting()) === $severity
			) {
				self::handleException(new \ErrorException($message, 0, $severity, $file, $line));
			}

			return false;
		});

		register_shutdown_function(function (): void {
			Assert::$onFailure = [self::class, 'handleException'];

			$error = error_get_last();
			register_shutdown_function(function () use ($error): void {
				if (in_array($error['type'] ?? null, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], strict: true)) {
					if (($error['type'] & error_reporting()) !== $error['type']) { // show fatal errors hidden by @shutup
						self::print("\n" . Ansi::colorize("Fatal error: $error[message] in $error[file] on line $error[line]", 'white/red'));
					}
				} elseif (self::$checkAssertions && !Assert::$counter) {
					self::print("\n" . Ansi::colorize('Error: This test forgets to execute an assertion.', 'white/red'));
					self::exit(Runner\Job::CodeFail);
				} elseif (!getenv(self::VariableRunner) && self::$exitCode !== Runner\Job::CodeSkip) {
					self::print("\n" . (self::$exitCode ? Ansi::colorize('FAILURE', 'white/red') : Ansi::colorize('OK', 'white/green')));
				}
			});
		});
	}


	/**
	 * Creates global functions test(), testException(), setUp() and tearDown().
	 */
	public static function setupFunctions(): void
	{
		require __DIR__ . '/functions.php';
	}


	/**
	 * @internal
	 */
	public static function handleException(\Throwable $e): void
	{
		self::$checkAssertions = false;
		self::print(Dumper::dumpException($e));
		self::exit($e instanceof AssertException ? Runner\Job::CodeFail : Runner\Job::CodeError);
	}


	/**
	 * Skips this test.
	 */
	public static function skip(string $message = ''): void
	{
		self::$checkAssertions = false;
		self::print("\nSkipped:\n$message");
		self::exit(Runner\Job::CodeSkip);
	}


	/**
	 * Prevents two parallel tests with the same name from running at the same time.
	 * @param  string  $path  directory where the lock file is created
	 */
	public static function lock(string $name = '', string $path = ''): void
	{
		static $locks;
		$file = "$path/lock-" . md5($name);
		if (!isset($locks[$file])) {
			$locks[$file] = fopen($file, 'w') ?: throw new \RuntimeException("Unable to create lock file '$file'.");
			flock($locks[$file], LOCK_EX);
		}
	}


	/**
	 * Returns annotations from the top-level test file's docblock.
	 * @return array<string|string[]>
	 */
	public static function getTestAnnotations(): array
	{
		$trace = debug_backtrace();
		return ($file = $trace[count($trace) - 1]['file'] ?? null)
			? Helpers::parseDocComment(Helpers::readFile($file)) + ['file' => $file]
			: [];
	}


	/**
	 * Strips the `final` keyword from PHP source files on load, allowing subclassing of final classes.
	 */
	public static function bypassFinals(): void
	{
		FileMutator::addMutator(function (string $code): string {
			if (str_contains($code, 'final')) {
				$tokens = \PhpToken::tokenize($code, TOKEN_PARSE);
				$code = '';
				foreach ($tokens as $token) {
					$code .= $token->is(T_FINAL) ? '' : $token->text;
				}
			}

			return $code;
		});
	}


	/**
	 * Returns the current data set passed via @dataProvider annotation or --dataprovider CLI argument.
	 * @return array<string, mixed>
	 */
	public static function loadData(): array
	{
		/** @var list<string> $argv */
		$argv = $_SERVER['argv'] ?? [];
		if ($argv && ($tmp = preg_filter('#--dataprovider=(.*)#Ai', '$1', $argv))) {
			[$key, $file] = explode('|', reset($tmp), 2);
			$data = DataProvider::load($file);
			if (!array_key_exists($key, $data)) {
				throw new \Exception("Missing dataset '$key' from data provider '$file'.");
			}

			return $data[$key];
		}

		$annotations = self::getTestAnnotations();
		if (!isset($annotations['dataprovider'])) {
			throw new \Exception('Missing annotation @dataProvider.');
		}

		$provider = (array) $annotations['dataprovider'];
		[$file, $query] = DataProvider::parseAnnotation($provider[0], $annotations['file']);

		$data = DataProvider::load($file, $query);
		if (!$data) {
			throw new \Exception("No datasets from data provider '$file'" . ($query ? " for query '$query'" : '') . '.');
		}

		return reset($data);
	}


	public static function exit(int $code = 0): void
	{
		self::$exitCode = $code;
		exit($code);
	}


	/** @internal */
	public static function print(string $s): void
	{
		$s = $s === '' || str_ends_with($s, "\n") ? $s : $s . "\n";
		if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
			fwrite(STDOUT, self::$useColors ? $s : Ansi::stripAnsi($s));
		} else {
			echo $s;
		}
	}
}
