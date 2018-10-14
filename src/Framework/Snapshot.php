<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester;


/**
 * Snapshot testing helper.
 */
class Snapshot
{
	public static $snapshotDir = 'snapshots';

	public static $updatedSnapshots = [];


	/**
	 * Compares value with a previously created snapshot.
	 */
	public static function match($value, string $snapshotName): void
	{
		$updateSnapshots = (bool) getenv(Environment::UPDATE_SNAPSHOTS);

		$testFile = $_SERVER['argv'][0];
		$snapshotFile = self::getSnapshotFile($testFile, $snapshotName);

		if (!file_exists($snapshotFile)) {
			if (!$updateSnapshots) {
				Assert::fail("Missing snapshot file '$snapshotFile', use --update-snapshots option to generate it.");
			}

			self::write($snapshotFile, $value);
		}

		$snapshot = self::read($snapshotFile);

		try {
			Assert::equal($snapshot, $value, "Snapshot $snapshotName");

		} catch (AssertException $e) {
			if (!$updateSnapshots) {
				throw $e;
			}

			self::write($snapshotFile, $value);
		}
	}


	private static function getSnapshotFile(string $testFile, string $snapshotName): string
	{
		$path = self::$snapshotDir . DIRECTORY_SEPARATOR . pathinfo($testFile, PATHINFO_FILENAME) . '.' . $snapshotName . '.phps';
		if (!preg_match('#/|\w:#A', self::$snapshotDir)) {
			$path = dirname($testFile) . DIRECTORY_SEPARATOR . $path;
		}
		return $path;
	}


	private static function read(string $snapshotFile)
	{
		$snapshotContents = @file_get_contents($snapshotFile);
		if ($snapshotContents === false) {
			throw new \Exception("Unable to read snapshot file '$snapshotFile'.");
		}

		return eval(substr($snapshotContents, strlen('<?php ')));
	}


	private static function write(string $snapshotFile, $value): void
	{
		$snapshotDirectory = dirname($snapshotFile);
		if (!is_dir($snapshotDirectory) && !mkdir($snapshotDirectory)) {
			throw new \Exception("Unable to create snapshot directory '$snapshotDirectory'.");
		}

		$snapshotContents = '<?php return ' . var_export($value, true) . ';' . PHP_EOL;
		if (file_put_contents($snapshotFile, $snapshotContents) === false) {
			throw new \Exception("Unable to write snapshot file '$snapshotFile'.");
		}

		self::$updatedSnapshots[] = $snapshotFile;
	}
}
