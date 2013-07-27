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
	 * Purges directory.
	 * @param  string
	 * @return void
	 */
	public static function purge($dir)
	{
		if (!is_dir($dir)) {
			mkdir($dir);
		}
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $entry) {
			if ($entry->isDir()) {
				rmdir($entry);
			} else {
				unlink($entry);
			}
		}
	}


	/**
	 * Parse phpDoc comment.
	 * @return array
	 * @internal
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
		preg_match_all('#^[ \t\*]*@(\w+)([^\w\r\n].*)?#mi', $content[1], $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$ref = & $options[strtolower($match[1])];
			if (isset($ref)) {
				$ref = (array) $ref;
				$ref = & $ref[];
			}
			$ref = isset($match[2]) ? trim($match[2]) : '';
		}
		return $options;
	}


	/** @deprecated */
	public static function skip($message = '')
	{
		Environment::skip($message);
	}


	/** @deprecated */
	public static function lock($name = '', $path = '')
	{
		Environment::lock($name, $path);
	}


	/** @deprecated */
	public static function setup()
	{
		Environment::setup();
	}

}
