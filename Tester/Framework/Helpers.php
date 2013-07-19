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
				Helpers::handleException(new \ErrorException($message, 0, $severity, $file, $line));
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


	/**
	 * Purges directory.
	 * @param  string
	 * @return void
	 */
	public static function purge($dir)
	{
		if (!is_dir($dir)) {
			mkdir($dir);
		}

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
	public static function handleException($e)
	{
		$s = self::$debugMode ? Dumper::dumpException($e) : "\nError: {$e->getMessage()}\n";
		echo static::detectColors() ? $s : Dumper::removeColors($s);
		exit($e instanceof AssertException ? Runner\Job::CODE_FAIL : Runner\Job::CODE_ERROR);
	}


	public static function with($obj, \Closure $closure)
	{
		return $closure->bindTo($obj, $obj)->__invoke();
	}


	/**
	 * Parse phpDoc comment.
	 * @return array
	 */
	public static function parseDocComment($s)
	{
		$options = array();
		if (!preg_match('#^/\*\*(.*?)\*/#ms', $s, $content)) {
			return array();
		}
		if (preg_match('#^[ \t\*]*+([^\s@].*)#mi', $content[1], $matches)) {
			$options[0] = trim($matches[1]);
		}
		preg_match_all('#^[ \t\*]*@(\S+)([ \t]+\S.*)?#mi', $content[1], $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$ref = & $options[strtolower($match[1])];
			if (isset($ref)) {
				$ref = (array) $ref;
				$ref = & $ref[];
			}
			$ref = isset($match[2]) ? trim($match[2]) : TRUE;
		}
		return $options;
	}


	/**
	 * @return bool
	 */
	public static function detectColors()
	{
		$colors = getenv('NETTE_TESTER_COLORS');
		return $colors === FALSE
			? (defined('STDOUT') && function_exists('posix_isatty') && posix_isatty(STDOUT))
			: (bool) $colors;
	}


	/**
	 * Dumps data to folder 'output'.
	 * @return void
	 */
	public static function dumpOutput($testFile, $content, $suffix = '')
	{
		$path = dirname($testFile) . '/output/' . basename($testFile, '.phpt') . $suffix;
		@mkdir(dirname($path)); // @ - directory may already exist
		file_put_contents($path, is_string($content) ? $content : Dumper::toPhp($content));
	}

}
