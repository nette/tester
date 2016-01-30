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
		if (self::$file) {
			throw new \LogicException('Code coverage collector has been already started.');
		}
		self::$file = fopen($file, 'a+');

		if (defined('PHPDBG_VERSION') && PHP_VERSION_ID >= 70000) {
			phpdbg_start_oplog();
			$collector = 'collectPhpDbg';

		} elseif (extension_loaded('xdebug')) {
			xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
			$collector = 'collectXdebug';

		} else {
			$alternative = PHP_VERSION_ID >= 70000 ? ' or phpdbg SAPI' : '';
			throw new \Exception("Code coverage functionality requires Xdebug extension$alternative.");
		}

		register_shutdown_function(function () use ($collector) {
			register_shutdown_function(function () use ($collector) {
				list($positive, $negative) = call_user_func(array(__CLASS__, $collector));
				self::save($positive, $negative);
			});
		});
	}


	/**
	 * Collects information about code coverage.
	 * @return array
	 */
	private static function collectXdebug()
	{
		$positive = $negative = array();

		foreach (xdebug_get_code_coverage() as $file => $lines) {
			if (!file_exists($file)) {
				continue;
			}

			foreach ($lines as $num => $val) {
				if ($val > 0) {
					$positive[$file][$num] = $val;
				} else {
					$negative[$file][$num] = $val;
				}
			}
		}

		return array($positive, $negative);
	}


	/**
	 * Collects information about code coverage.
	 * @return array
	 */
	private static function collectPhpDbg()
	{
		$positive = phpdbg_end_oplog();
		$negative = phpdbg_get_executable();

		foreach ($positive as $file => & $lines) {
			$lines = array_fill_keys(array_keys($lines), 1);
		}

		foreach ($negative as $file => & $lines) {
			$lines = array_fill_keys(array_keys($lines), -1);
		}

		return array($positive, $negative);
	}


	/**
	 * Saves information about code coverage. Do not call directly.
	 * @return void
	 */
	private static function save(array $positive, array $negative)
	{
		flock(self::$file, LOCK_EX);
		fseek(self::$file, 0);
		$original = @unserialize(stream_get_contents(self::$file)) ?: array(); // @ file may be empty
		$coverage = array_replace_recursive($negative, $original, $positive);

		ftruncate(self::$file, 0);
		fwrite(self::$file, serialize($coverage));
		fclose(self::$file);
	}

}
