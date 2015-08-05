<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\CodeCoverage;

use Tester\Environment;
use Tester\Helpers;


/**
 * Code coverage collector.
 */
class Collector
{
	const COVER_NOTHING = 1;
	const COVER_ALL = 2;


	/** @var resource */
	protected static $file;


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


	protected static function getCoverAnnotations()
	{
		global $argv;
		$testFile = $argv[0];
		return Helpers::parseDocComment(file_get_contents($testFile));
	}


	/**
	 * @return int|array[] (filename => \Reflector[])
	 * @throws \ReflectionException
	 */
	protected static function getCoverFilters()
	{
		$annotations = static::getCoverAnnotations();
		if (isset($annotations['coversNothing'])) {
			if (isset($annotations['covers'])) {
				throw new \Exception('Using both @covers and @coversNothing is not supported');
			}

			return self::COVER_NOTHING;
		}

		if (!isset($annotations['covers'])) {
			// TODO warn user to use covers
			return self::COVER_ALL;
		}

		$filters = array();
		foreach ((array) $annotations['covers'] as $name) {
			$ref = NULL;
			try {
				if (strpos($name, '::') !== FALSE) {
					$ref = new \ReflectionMethod(rtrim($name, '()'));

				} else {
					$ref = new \ReflectionClass($name);
				}

			} catch (\ReflectionException $e) {
				throw new \Exception("Failed to find '$name' when generating coverage", NULL, $e);
			}

			$filters[$ref->getFileName()][] = $ref;
		}

		return $filters;
	}


	/**
	 * @param \ReflectionClass[]|\ReflectionMethod[] $refs
	 * @param int                                    $line
	 * @return bool
	 */
	private static function isCovered(array $refs, $line)
	{
		foreach ($refs as $ref) {
			if ($line >= $ref->getStartLine() && $line <= $ref->getEndLine()) {
				return TRUE;
			}
		}
		return FALSE;
	}


	/**
	 * Saves information about code coverage. Do not call directly.
	 * @return void
	 * @internal
	 */
	public static function save()
	{
		$filters = static::getCoverFilters();

		flock(self::$file, LOCK_EX);
		fseek(self::$file, 0);
		$coverage = @unserialize(stream_get_contents(self::$file)); // @ file may be empty

		foreach (xdebug_get_code_coverage() as $filename => $lines) {
			if (!file_exists($filename)) {
				continue;
			}

			$refs = isset($filters[$filename]) ? $filters[$filename] : array();
			foreach ($lines as $num => $val) {
				if ($filters === self::COVER_NOTHING || ($filters !== self::COVER_ALL && !static::isCovered($refs, $num))) {
					$val = -1;
				}

				if (empty($coverage[$filename][$num]) || $val > 0) {
					$coverage[$filename][$num] = $val; // -1 => untested; -2 => dead code
				}
			}
		}

		ftruncate(self::$file, 0);
		fwrite(self::$file, serialize($coverage));
		fclose(self::$file);
	}

}
