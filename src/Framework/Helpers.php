<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester;


/**
 * Test helpers.
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


	/**
	 * @internal
	 */
	public static function errorTypeToString($type)
	{
		$consts = get_defined_constants(TRUE);
		foreach ($consts['Core'] as $name => $val) {
			if ($type === $val && substr($name, 0, 2) === 'E_') {
				return $name;
			}
		}
	}


	/**
	 * Escape a string to be used as a shell argument.
	 * @return string
	 */
	public static function escapeArg($s)
	{
		if (preg_match('#^[a-z0-9._-]+\z#i', $s)) {
			return $s;
		}

		return defined('PHP_WINDOWS_VERSION_BUILD')
			? '"' . str_replace('"', '""', $s) . '"'
			: escapeshellarg($s);
	}

}
