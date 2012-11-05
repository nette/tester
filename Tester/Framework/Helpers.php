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
	/** @var array */
	static public $notes = array();


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
	 * Log info.
	 * @return void
	 */
	public static function note($message)
	{
		self::$notes[] = $message;
	}



	/**
	 * Returns notes.
	 * @return array
	 */
	public static function fetchNotes()
	{
		$res = self::$notes;
		self::$notes = array();
		return $res;
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

}
