<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester;


/**
 * Testing environment.
 *
 * @author     David Grudl
 */
class Environment
{
	/** Should Tester use console colors? */
	const COLORS = 'NETTE_TESTER_COLORS';

	/** Test is runned by Runner */
	const RUNNER = 'NETTE_TESTER_RUNNER';

	/** Test is run by FastCGI */
	const FCGI = 'NETTE_TESTER_FCGI';

	/** @internal */
	const FCGI_ARGS = 'NETTE_TESTER_FCGI_ARGS';

	/** @internal */
	const FCGI_INI = 'NETTE_TESTER_FCGI_INI';

	/** Code coverage file */
	const COVERAGE = 'NETTE_TESTER_COVERAGE';

	/** @var bool  used for debugging Tester itself */
	public static $debugMode = TRUE;

	/** @var bool */
	public static $checkAssertions = TRUE;

	/** @var bool */
	public static $useColors;

	/** @var bool */
	private static $asFcgi = FALSE;


	/**
	 * Configures PHP environment.
	 * @return void
	 */
	public static function setup()
	{
		self::$useColors = getenv(self::COLORS) !== FALSE
			? (bool) getenv(self::COLORS)
			: (PHP_SAPI === 'cli' && ((function_exists('posix_isatty') && posix_isatty(STDOUT))
				|| getenv('ConEmuANSI') === 'ON' || getenv('ANSICON') !== FALSE));

		self::$asFcgi = (bool) getenv(self::FCGI);

		class_exists('Tester\Runner\Job');
		class_exists('Tester\Dumper');
		class_exists('Tester\Assert');

		error_reporting(E_ALL | E_STRICT);
		ini_set('display_errors', TRUE);
		ini_set('html_errors', FALSE);
		ini_set('log_errors', FALSE);

		$annotations = self::getTestAnnotations();
		if (isset($annotations['outputmatch']) || isset($annotations['outputmatchfile'])) {
			self::$checkAssertions = FALSE;
		}

		set_exception_handler(array(__CLASS__, 'handleException'));

		set_error_handler(function($severity, $message, $file, $line) {
			if (in_array($severity, array(E_RECOVERABLE_ERROR, E_USER_ERROR)) || ($severity & error_reporting()) === $severity) {
				Environment::handleException(new \ErrorException($message, 0, $severity, $file, $line));
			}
			return FALSE;
		});

		register_shutdown_function(function() {
			Assert::$onFailure = array(__CLASS__, 'handleException'); // note that Runner is unable to catch this errors in CLI & PHP 5.4.0 - 5.4.6 due PHP bug #62725

			$error = error_get_last();
			register_shutdown_function(function() use ($error) {
				if (in_array($error['type'], array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE))) {
					if (($error['type'] & error_reporting()) !== $error['type']) { // show fatal errors hidden by @shutup
						echo "\nFatal error: $error[message] in $error[file] on line $error[line]\n";
					}
				} elseif (Environment::$checkAssertions && !Assert::$counter) {
					echo "\nError: This test forgets to execute an assertion.\n";
					Environment::callExit(Runner\Job::CODE_FAIL);
				}
			});

			Environment::callExit(0, FALSE);
		});

		if (getenv(self::COVERAGE)) {
			CodeCoverage\Collector::start(getenv(self::COVERAGE));
		}

		if (self::$asFcgi) {
			foreach (unserialize(getenv(self::FCGI_INI)) as $name => $value) {
				ini_set($name, $value);
			}

			$_SERVER['argv'] = unserialize(getenv(self::FCGI_ARGS));
			$_SERVER['argc'] = count($_SERVER['argv']);
		}

		ob_start(function($s) {
			return Environment::$useColors ? $s : Dumper::removeColors($s);
		}, PHP_VERSION_ID < 50400 ? 2 : 1);
	}


	/** @internal */
	public static function handleException($e)
	{
		self::$checkAssertions = FALSE;
		echo self::$debugMode ? Dumper::dumpException($e) : "\nError: {$e->getMessage()}\n";
		Environment::callExit($e instanceof AssertException ? Runner\Job::CODE_FAIL : Runner\Job::CODE_ERROR);
	}


	/** @internal */
	final public static function callExit($code, $realExit = TRUE)
	{
		if (self::$asFcgi) {
			echo "\nNETTE_TESTER_FCGI_EXIT_CODE:$code\n";
		}

		if ($realExit) {
			exit($code);
		}
	}


	/**
	 * Skips this test.
	 * @return void
	 */
	public static function skip($message = '')
	{
		self::$checkAssertions = FALSE;
		echo "\nSkipped:\n$message\n";
		self::callExit(Runner\Job::CODE_SKIP);
	}


	/**
	 * locks the parallel tests.
	 * @return void
	 */
	public static function lock($name = '', $path = '')
	{
		static $lock;
		flock($lock = fopen($path . '/lock-' . md5($name), 'w'), LOCK_EX);
	}


	/**
	 * Returns current test annotations.
	 * @return array
	 */
	public static function getTestAnnotations()
	{
		$trace = debug_backtrace();
		$file = $trace[count($trace) - 1]['file'];
		return Helpers::parseDocComment(file_get_contents($file)) + array('file' => $file);
	}


	/**
	 * Loads data according to the file annotation or specified by Tester\Runner\TestHandler::initiateDataProvider()
	 * @return array
	 */
	public static function loadData()
	{
		if (isset($_SERVER['argv'][2])) {
			list(, $query, $file) = $_SERVER['argv'];

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

}
