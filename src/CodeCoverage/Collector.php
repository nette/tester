<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester\CodeCoverage;


/**
 * Code coverage collector.
 */
class Collector
{
	public const
		ENGINE_PHPDBG = 'PHPDBG',
		ENGINE_XDEBUG = 'Xdebug';

	/** @var resource */
	private static $file;

	/** @var string */
	private static $collector;


	public static function detectEngines(): array
	{
		$engines = [];
		if (defined('PHPDBG_VERSION')) {
			$engines[] = self::ENGINE_PHPDBG;
		}

		if (extension_loaded('xdebug')) {
			$engines[] = self::ENGINE_XDEBUG;
		}

		return $engines;
	}


	public static function isStarted(): bool
	{
		return self::$file !== null;
	}


	/**
	 * Starts gathering the information for code coverage.
	 * @throws \LogicException
	 */
	public static function start(string $file, string $engine): void
	{
		if (self::isStarted()) {
			throw new \LogicException('Code coverage collector has been already started.');
		}
		self::$file = fopen($file, 'c+');

		switch ($engine) {
			case self::ENGINE_PHPDBG:
				phpdbg_start_oplog();
				self::$collector = 'collectPhpDbg';
				break;

			case self::ENGINE_XDEBUG:
				xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
				self::$collector = 'collectXdebug';
				break;

			default:
				throw new \LogicException("Code coverage engine '$engine' is not supported.");
		}

		register_shutdown_function(function (): void {
			register_shutdown_function([__CLASS__, 'save']);
		});
	}


	/**
	 * Flushes all gathered information. Effective only with PHPDBG collector.
	 */
	public static function flush(): void
	{
		if (self::isStarted() && self::$collector === 'collectPhpDbg') {
			self::save();
		}
	}


	/**
	 * Saves information about code coverage. Can be called repeatedly to free memory.
	 * @throws \LogicException
	 */
	public static function save(): void
	{
		if (!self::isStarted()) {
			throw new \LogicException('Code coverage collector has not been started.');
		}

		[$positive, $negative] = [__CLASS__, self::$collector]();

		flock(self::$file, LOCK_EX);
		fseek(self::$file, 0);
		$rawContent = stream_get_contents(self::$file);
		$original = $rawContent ? unserialize($rawContent) : [];
		$coverage = array_replace_recursive($negative, $original, $positive);

		fseek(self::$file, 0);
		ftruncate(self::$file, 0);
		fwrite(self::$file, serialize($coverage));
		flock(self::$file, LOCK_UN);
	}


	/**
	 * Collects information about code coverage.
	 */
	private static function collectXdebug(): array
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
	 */
	private static function collectPhpDbg(): array
	{
		$positive = phpdbg_end_oplog();
		$negative = phpdbg_get_executable();

		foreach ($positive as $file => &$lines) {
			$lines = array_fill_keys(array_keys($lines), 1);
		}

		foreach ($negative as $file => &$lines) {
			$lines = array_fill_keys(array_keys($lines), -1);
		}

		phpdbg_start_oplog();
		return [$positive, $negative];
	}
}
