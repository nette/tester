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
 * Test helpers.
 *
 * @author     David Grudl
 */
class Helpers
{

	/**
	 * Configures PHP environment.
	 * @return void
	 */
	public static function setup()
	{
		error_reporting(E_ALL | E_STRICT);
		ini_set('display_errors', TRUE);
		ini_set('html_errors', FALSE);
		ini_set('log_errors', FALSE);
		set_error_handler(array(__CLASS__, 'handleError'));
		set_exception_handler(array(__CLASS__, 'handleException'));
	}



	/**
	 * Purges directory.
	 * @param  string
	 * @return void
	 */
	public static function purge($dir)
	{
		@mkdir($dir, 0777, TRUE); // @ - directory may already exist
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir), \RecursiveIteratorIterator::CHILD_FIRST) as $entry) {
			if (substr($entry->getBasename(), 0, 1) === '.') { // . or .. or .gitignore
				// ignore
			} elseif ($entry->isDir()) {
				rmdir($entry);
			} else {
				unlink($entry);
			}
		}
	}



	/**
	 * Skips this test.
	 * @return void
	 */
	public static function skip($message = '')
	{
		echo "\nSkipped:\n$message\n";
		die(\Tester\Runner\Job::CODE_SKIP);
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



	/** @internal */
	public static function handleError($severity, $message, $file, $line)
	{
		if (($severity & error_reporting()) === $severity) {
			$e = new \ErrorException($message, 0, $severity, $file, $line);
			echo "\nError: $message in $file:$line\nStack trace:\n" . $e->getTraceAsString();
			exit(Runner\Job::CODE_ERROR);
		}
		return FALSE;
	}



	/** @internal */
	public static function handleException($e)
	{
		echo "\n" . ($e instanceof AssertException ? '' : get_class($e) . ': ') . $e->getMessage();
		$trace = $e->getTrace();
		while (isset($trace[0]['file']) && substr($trace[0]['file'], strlen(__DIR__))  === __DIR__) {
			array_shift($trace);
		}
		while ($trace) {
			if (isset($trace[0]['file'], $trace[0]['line'])) {
				echo "\nin " . implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $trace[0]['file']), -3)) . ':' . $trace[0]['line'];
			}
			array_shift($trace);
		}
		exit($e instanceof AssertException ? Runner\Job::CODE_FAIL : Runner\Job::CODE_ERROR);
	}

}
