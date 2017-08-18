<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester;


/**
 * Testing environment.
 */
class Environment
{
	/** Should Tester use console colors? */
	const COLORS = 'NETTE_TESTER_COLORS';

	/** Test is run by Runner */
	const RUNNER = 'NETTE_TESTER_RUNNER';

	/** Code coverage file */
	const COVERAGE = 'NETTE_TESTER_COVERAGE';

	/** Thread number when run tests in multi threads */
	const THREAD = 'NETTE_TESTER_THREAD';

	/** @var bool  used for debugging Tester itself */
	public static $debugMode = true;

	/** @var bool */
	public static $checkAssertions = false;

	/** @var bool */
	public static $useColors;

	/** @var int initial output buffer level */
	private static $obLevel;


	/**
	 * Configures testing environment.
	 * @return void
	 */
	public static function setup()
	{
		self::setupErrors();
		self::setupColors();
		self::$obLevel = ob_get_level();

		class_exists('Tester\Runner\Job');
		class_exists('Tester\Dumper');
		class_exists('Tester\Assert');

		$annotations = self::getTestAnnotations();
		self::$checkAssertions = !isset($annotations['outputmatch']) && !isset($annotations['outputmatchfile']);

		if (getenv(self::COVERAGE)) {
			CodeCoverage\Collector::start(getenv(self::COVERAGE));
		}
	}


	/**
	 * Configures colored output.
	 * @return void
	 */
	public static function setupColors()
	{
		self::$useColors = getenv(self::COLORS) !== false
			? (bool) getenv(self::COLORS)
			: ((PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg')
				&& ((function_exists('posix_isatty') && posix_isatty(STDOUT))
					|| getenv('ConEmuANSI') === 'ON' || getenv('ANSICON') !== false) || getenv('TERM') === 'xterm-256color');

		ob_start(function ($s) {
			return self::$useColors ? $s : Dumper::removeColors($s);
		}, 1, PHP_OUTPUT_HANDLER_FLUSHABLE);
	}


	/**
	 * Configures PHP error handling.
	 * @return void
	 */
	public static function setupErrors()
	{
		error_reporting(E_ALL);
		ini_set('display_errors', '1');
		ini_set('html_errors', '0');
		ini_set('log_errors', '0');

		set_exception_handler([__CLASS__, 'handleException']);

		set_error_handler(function ($severity, $message, $file, $line) {
			if (in_array($severity, [E_RECOVERABLE_ERROR, E_USER_ERROR], true) || ($severity & error_reporting()) === $severity) {
				self::handleException(new \ErrorException($message, 0, $severity, $file, $line));
			}
			return false;
		});

		register_shutdown_function(function () {
			Assert::$onFailure = [__CLASS__, 'handleException'];

			$error = error_get_last();
			register_shutdown_function(function () use ($error) {
				if (in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
					if (($error['type'] & error_reporting()) !== $error['type']) { // show fatal errors hidden by @shutup
						self::removeOutputBuffers();
						echo "\nFatal error: $error[message] in $error[file] on line $error[line]\n";
					}
				} elseif (self::$checkAssertions && !Assert::$counter) {
					self::removeOutputBuffers();
					echo "\nError: This test forgets to execute an assertion.\n";
					exit(Runner\Job::CODE_FAIL);
				}
			});
		});
	}


	/**
	 * @param  \Exception|\Throwable
	 * @internal
	 */
	public static function handleException($e)
	{
		self::removeOutputBuffers();
		self::$checkAssertions = false;
		echo self::$debugMode ? Dumper::dumpException($e) : "\nError: {$e->getMessage()}\n";
		exit($e instanceof AssertException ? Runner\Job::CODE_FAIL : Runner\Job::CODE_ERROR);
	}


	/**
	 * Skips this test.
	 * @return void
	 */
	public static function skip($message = '')
	{
		self::$checkAssertions = false;
		echo "\nSkipped:\n$message\n";
		die(Runner\Job::CODE_SKIP);
	}


	/**
	 * Locks the parallel tests.
	 * @param  string
	 * @param  string  lock store directory
	 * @return void
	 */
	public static function lock($name = '', $path = '')
	{
		static $locks;
		$file = "$path/lock-" . md5($name);
		if (!isset($locks[$file])) {
			flock($locks[$file] = fopen($file, 'w'), LOCK_EX);
		}
	}


	/**
	 * Returns current test annotations.
	 * @return array
	 */
	public static function getTestAnnotations()
	{
		$trace = debug_backtrace();
		$file = $trace[count($trace) - 1]['file'];
		return Helpers::parseDocComment(file_get_contents($file)) + ['file' => $file];
	}


	/**
	 * Removes keyword final from source codes.
	 * @return void
	 */
	public static function bypassFinals()
	{
		FileMutator::addMutator(function ($code) {
			if (strpos($code, 'final') !== false) {
				$tokens = token_get_all($code);
				$code = '';
				foreach ($tokens as $token) {
					$code .= is_array($token)
						? ($token[0] === T_FINAL ? '' : $token[1])
						: $token;
				}
			}
			return $code;
		});
	}


	/**
	 * Loads data according to the file annotation or specified by Tester\Runner\TestHandler::initiateDataProvider()
	 * @return array
	 */
	public static function loadData()
	{
		if (isset($_SERVER['argv']) && ($tmp = preg_filter('#--dataprovider=(.*)#Ai', '$1', $_SERVER['argv']))) {
			list($query, $file) = explode('|', reset($tmp), 2);

		} else {
			$annotations = self::getTestAnnotations();
			if (!isset($annotations['dataprovider'])) {
				throw new \Exception('Missing annotation @dataProvider.');
			}
			$provider = (array) $annotations['dataprovider'];
			list($file, $query) = DataProvider::parseAnnotation($provider[0], $annotations['file']);
		}
		$data = DataProvider::load($file, $query);
		return reset($data);
	}


	private static function removeOutputBuffers()
	{
		while (ob_get_level() > self::$obLevel && @ob_end_flush()); // @ may be not removable
	}
}
