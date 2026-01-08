<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester\CodeCoverage\Generators;

use Tester\Helpers;
use function in_array, is_array;
use const ARRAY_FILTER_USE_KEY, STDOUT;


/**
 * Code coverage report generator.
 */
abstract class AbstractGenerator
{
	protected const
		LineDead = -2,
		LineTested = 1,
		LineUntested = -1;

	/** @var string[]  file extensions to accept */
	public array $acceptFiles = ['php', 'phpt', 'phtml'];

	/** @var array<string, array<int, int>>  file path => line number => coverage count */
	protected array $data;

	/** @var string[]  source paths */
	protected array $sources;
	protected int $totalSum = 0;
	protected int $coveredSum = 0;


	/**
	 * @param  string  $file  path to coverage.dat file
	 * @param  string[]  $sources  paths to covered source files or directories
	 */
	public function __construct(string $file, array $sources = [])
	{
		$data = @unserialize(Helpers::readFile($file)); // @ - unserialization may fail
		if (!is_array($data)) {
			throw new \Exception("Content of file '$file' is invalid.");
		}

		$this->data = array_filter($data, fn(string $path): bool => @is_file($path), ARRAY_FILTER_USE_KEY);

		if (!$sources) {
			$sources = [Helpers::findCommonDirectory(array_keys($this->data))];

		} else {
			foreach ($sources as $source) {
				if (!file_exists($source)) {
					throw new \Exception("File or directory '$source' is missing.");
				}
			}
		}

		$this->sources = array_map('realpath', $sources);
	}


	public function render(?string $file = null): void
	{
		$handle = $file ? @fopen($file, 'w') : STDOUT; // @ is escalated to exception
		if (!$handle) {
			throw new \Exception("Unable to write to file '$file'.");
		}

		ob_start(function (string $buffer) use ($handle) {
			fwrite($handle, $buffer);
			return '';
		}, 4096);
		try {
			$this->renderSelf();
		} catch (\Throwable $e) {
		}

		ob_end_flush();
		fclose($handle);

		if (isset($e)) {
			if ($file) {
				unlink($file);
			}

			throw $e;
		}
	}


	public function getCoveredPercent(): float
	{
		return $this->totalSum ? $this->coveredSum * 100 / $this->totalSum : 0;
	}


	protected function getSourceIterator(): \Iterator
	{
		$iterator = new \AppendIterator;
		foreach ($this->sources as $source) {
			$iterator->append(
				is_dir($source)
					? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source))
					: new \ArrayIterator([new \SplFileInfo($source)]),
			);
		}

		return new \CallbackFilterIterator(
			$iterator,
			fn(\SplFileInfo $file): bool => $file->getBasename()[0] !== '.'  // . or .. or .gitignore
				&& in_array($file->getExtension(), $this->acceptFiles, strict: true),
		);
	}


	/**
	 * @param string[] $files
	 * @deprecated
	 */
	protected static function getCommonFilesPath(array $files): string
	{
		return Helpers::findCommonDirectory($files);
	}


	abstract protected function renderSelf(): void;
}
