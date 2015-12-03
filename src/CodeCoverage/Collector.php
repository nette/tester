<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\CodeCoverage;

/**
 * Code coverage collector.
 */
class Collector
{
	/** @var resource */
	private static $file;


	/**
	 * Starts gathering the information for code coverage.
	 * @param  string
	 * @return void
	 */
	public static function start($file)
	{
		if (!extension_loaded('xdebug')) {
			throw new \Exception('Code coverage functionality requires Xdebug extension.');
		} elseif (self::$file) {
			throw new \LogicException('Code coverage collector has been already started.');
		}

		self::$file = fopen($file, 'a+');
		xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
		register_shutdown_function(function () {
			register_shutdown_function(array(__CLASS__, 'save'));
		});
	}


	/**
	 * Saves information about code coverage. Do not call directly.
	 * @return void
	 * @internal
	 */
	public static function save()
	{
		$negative = array();
		$positive = array();
		foreach (xdebug_get_code_coverage() as $filename => $lines) {
			if (!file_exists($filename)) {
				continue;
			}

			$pnode = &$positive[$filename];
			$pnode = array();
			$nnode = &$negative[$filename];
			$nnode = array();

			foreach ($lines as $num => $val) {
				$val > 0 ? $pnode[$num] = $val : $nnode[$num] = $val;
			}
		}

		flock(self::$file, LOCK_EX);
		fseek(self::$file, 0);
		$original = @unserialize(stream_get_contents(self::$file)) ?: array(); // @ file may be empty

		$coverage = array_replace_recursive($negative, $original, $positive);

		ftruncate(self::$file, 0);
		fwrite(self::$file, serialize($coverage));
		fclose(self::$file);
	}

}
