<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester;


/**
 * Test helpers.
 */
class Helpers
{
	/**
	 * Purges directory.
	 */
	public static function purge(string $dir): void
	{
		if (!is_dir($dir)) {
			mkdir($dir);
		}
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $entry) {
			if ($entry->isDir()) {
				rmdir((string) $entry);
			} else {
				unlink((string) $entry);
			}
		}
	}


	/**
	 * Parse phpDoc comment.
	 * @internal
	 */
	public static function parseDocComment(string $s): array
	{
		$options = [];
		if (!preg_match('#^/\*\*(.*?)\*/#ms', $s, $content)) {
			return [];
		}
		if (preg_match('#^[ \t\*]*+([^\s@].*)#mi', $content[1], $matches)) {
			$options[0] = trim($matches[1]);
		}
		preg_match_all('#^[ \t\*]*@(\w+)([^\w\r\n].*)?#mi', $content[1], $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$ref = &$options[strtolower($match[1])];
			if (isset($ref)) {
				$ref = (array) $ref;
				$ref = &$ref[];
			}
			$ref = isset($match[2]) ? trim($match[2]) : '';
		}
		return $options;
	}


	/**
	 * @internal
	 */
	public static function errorTypeToString(int $type): string
	{
		$consts = get_defined_constants(true);
		foreach ($consts['Core'] as $name => $val) {
			if ($type === $val && substr($name, 0, 2) === 'E_') {
				return $name;
			}
		}
	}


	/**
	 * Escape a string to be used as a shell argument.
	 */
	public static function escapeArg(string $s): string
	{
		if (preg_match('#^[a-z0-9._=/:-]+\z#i', $s)) {
			return $s;
		}

		return defined('PHP_WINDOWS_VERSION_BUILD')
			? '"' . str_replace('"', '""', $s) . '"'
			: escapeshellarg($s);
	}
}
