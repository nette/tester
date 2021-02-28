<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester;


/**
 * Snapshot of a tested value.
 * @internal
 */
class Snapshot
{
	/** @var string */
	public static $snapshotDir = 'snapshots';

	/** @var string[] */
	public static $updatedSnapshots = [];

	/** @var string[] */
	private static $usedNames = [];

	/** @var string */
	private $name;


	public function __construct(string $name)
	{
		if (!preg_match('/^[a-zA-Z0-9-_]+$/', $name)) {
			throw new \Exception("Invalid snapshot name '$name'. Only alphanumeric characters, dash and underscore are allowed.");
		}

		if (in_array($name, self::$usedNames, true)) {
			throw new \Exception("Snapshot '$name' was already asserted, please use a different name.");
		}

		$this->name = self::$usedNames[] = $name;
	}


	public function exists(): bool
	{
		return file_exists($this->getSnapshotFile());
	}


	public function read()
	{
		$snapshotFile = $this->getSnapshotFile();
		set_error_handler(function ($errno, $errstr) use ($snapshotFile) {
			throw new \Exception("Unable to read snapshot file '$snapshotFile': $errstr");
		});

		$snapshotContents = include $snapshotFile;

		restore_error_handler();
		return $snapshotContents;
	}


	public function canUpdate(): bool
	{
		return (bool) getenv(Environment::UPDATE_SNAPSHOTS);
	}


	public function update($value): void
	{
		if (!$this->canUpdate()) {
			throw new \Exception('Cannot update snapshot. Please run tests again with --update-snapshots.');
		}

		$snapshotFile = $this->getSnapshotFile();
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


	private function getSnapshotFile(): string
	{
		$testFile = $_SERVER['argv'][0];
		$path = self::$snapshotDir . DIRECTORY_SEPARATOR . pathinfo($testFile, PATHINFO_FILENAME) . '.' . $this->name . '.phps';
		if (!preg_match('#/|\w:#A', self::$snapshotDir)) {
			$path = dirname($testFile) . DIRECTORY_SEPARATOR . $path;
		}
		return $path;
	}
}
