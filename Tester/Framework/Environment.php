<?php

/**
 * This file is part of the Nette Tester.
 *
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Tester;


/**
 * Testing environment.
 *
 * @author     David Grudl
 */
class Environment
{
	/** @var bool  used for debugging Tester itself */
	public static $debugMode = TRUE;


	/**
	 * Configures PHP environment.
	 * @return void
	 */
	public static function setup()
	{
		class_exists('Tester\Runner\Job');
		error_reporting(E_ALL | E_STRICT);
		ini_set('display_errors', TRUE);
		ini_set('html_errors', FALSE);
		ini_set('log_errors', FALSE);

		set_exception_handler(array(__CLASS__, 'handleException'));
		set_error_handler(function($severity, $message, $file, $line) {
			if (in_array($severity, array(E_RECOVERABLE_ERROR, E_USER_ERROR)) || ($severity & error_reporting()) === $severity) {
				Environment::handleException(new \ErrorException($message, 0, $severity, $file, $line));
			}
			return FALSE;
		});
		register_shutdown_function(function() {
			$error = error_get_last();
			if (in_array($error['type'], array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE)) && ($error['type'] & error_reporting()) !== $error['type']) {
				register_shutdown_function(function() use ($error) {
					echo "\nFatal error: $error[message] in $error[file] on line $error[line]\n";
				});
			}
		});
	}


	/** @internal */
	public static function handleException($e)
	{
		$s = self::$debugMode ? Dumper::dumpException($e) : "\nError: {$e->getMessage()}\n";
		echo static::hasColors() ? $s : Dumper::removeColors($s);
		exit($e instanceof AssertException ? Runner\Job::CODE_FAIL : Runner\Job::CODE_ERROR);
	}


	/**
	 * @return bool
	 * @internal
	 */
	public static function hasColors()
	{
		$colors = getenv('NETTE_TESTER_COLORS');
		return $colors === FALSE
			? (defined('STDOUT') && function_exists('posix_isatty') && posix_isatty(STDOUT))
			: (bool) $colors;
	}


	/**
	 * Skips this test.
	 * @return void
	 */
	public static function skip($message = '')
	{
		echo "\nSkipped:\n$message\n";
		die(Runner\Job::CODE_SKIP);
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

}
