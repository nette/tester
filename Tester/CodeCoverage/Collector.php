<?php

/**
 * This file is part of the Nette Tester.
 *
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Tester\CodeCoverage;


/**
 * Code coverage collector.
 *
 * @author     David Grudl
 */
class Collector
{
	/** @var string */
	static public $file;


	/**
	 * Starts gathering the information for code coverage.
	 * @param  string
	 * @return void
	 */
	public static function start($file)
	{
		self::$file = $file;
		xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
		register_shutdown_function(function() {
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
		$f = fopen(self::$file, 'a+');
		flock($f, LOCK_EX);
		fseek($f, 0);
		$coverage = @unserialize(stream_get_contents($f));

		foreach (xdebug_get_code_coverage() as $filename => $lines) {
			foreach ($lines as $num => $val) {
				if (empty($coverage[$filename][$num]) || $val > 0) {
					$coverage[$filename][$num] = $val; // -1 => untested; -2 => dead code
				}
			}
		}

		ftruncate($f, 0);
		fwrite($f, serialize($coverage));
		fclose($f);
	}

}
