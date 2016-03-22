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

	/** @var string */
	private static $collector;


	/**
	 * @return bool
	 */
	public static function isStarted()
	{
		return self::$file !== NULL;
	}


	/**
	 * Starts gathering the information for code coverage.
	 * @param  string
	 * @return void
	 */
	public static function start($file)
	{
		if (self::isStarted()) {
			throw new \LogicException('Code coverage collector has been already started.');
		}
		self::$file = fopen($file, 'c+');

		if (defined('PHPDBG_VERSION') && PHP_VERSION_ID >= 70000) {
			phpdbg_start_oplog();
			self::$collector = 'collectPhpDbg';

		} elseif (extension_loaded('xdebug')) {
			xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
			self::$collector = 'collectXdebug';

		} else {
			$alternative = PHP_VERSION_ID >= 70000 ? ' or phpdbg SAPI' : '';
			throw new \Exception("Code coverage functionality requires Xdebug extension$alternative.");
		}

		register_shutdown_function(function () {
			register_shutdown_function([__CLASS__, 'save']);
		});
	}


	/**
	 * Saves information about code coverage. Can be called repeatedly to free memory.
	 * @return void
	 */
	public static function save()
	{
		list($positive, $negative) = call_user_func([__CLASS__, self::$collector]);

		flock(self::$file, LOCK_EX);
		fseek(self::$file, 0);
		$rawContent = stream_get_contents(self::$file);
		$original = $rawContent ? unserialize($rawContent) : [];
		$coverage = array_replace_recursive($negative, $original, $positive);

		fseek(self::$file, 0);
		ftruncate(self::$file, 0);
		fwrite(self::$file, serialize($coverage));
		flock(self::$file, LOCK_UN);

		if (self::$collector === 'collectPhpDbg') {
			phpdbg_start_oplog();
		}
	}


	/**
	 * Collects information about code coverage.
	 * @return array
	 */
	private static function collectXdebug()
	{
		$positive = $negative = [];

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

		return [$positive, $negative];
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

		return [$positive, $negative];
	}

}
