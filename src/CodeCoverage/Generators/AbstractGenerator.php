<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester\CodeCoverage\Generators;


/**
 * Code coverage report generator.
 */
abstract class AbstractGenerator
{
	protected const
		CODE_DEAD = -2,
		CODE_UNTESTED = -1,
		CODE_TESTED = 1;

	/** @var array */
	public $acceptFiles = ['php', 'phpt', 'phtml'];

	/** @var array */
	protected $data;

	/** @var array */
	protected $sources;

	/** @var int */
	protected $totalSum = 0;

	/** @var int */
	protected $coveredSum = 0;


	/**
	 * @param  string  $file  path to coverage.dat file
	 * @param  array   $sources  paths to covered source files or directories
	 */
	public function __construct(string $file, array $sources = [])
	{
		if (!is_file($file)) {
			throw new \Exception("File '$file' is missing.");
		}

		$this->data = @unserialize(file_get_contents($file)); // @ is escalated to exception
		if (!is_array($this->data)) {
			throw new \Exception("Content of file '$file' is invalid.");
		}

		if (!$sources) {
			$sources = [$this->getCommonFilesPath(array_keys($this->data))];

		} else {
			foreach ($sources as $source) {
				if (!file_exists($source)) {
					throw new \Exception("File or directory '$source' is missing.");
				}
			}
		}

		$this->sources = array_map('realpath', $sources);
	}


	public function render(string $file = null): void
	{
		$handle = $file ? @fopen($file, 'w') : STDOUT; // @ is escalated to exception
		if (!$handle) {
			throw new \Exception("Unable to write to file '$file'.");
		}

		ob_start(function (string $buffer) use ($handle) { fwrite($handle, $buffer); }, 4096);
		try {
			$this->renderSelf();
		} catch (\Exception $e) {
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
					: new \ArrayIterator([new \SplFileInfo($source)])
			);
		}

		return new \CallbackFilterIterator($iterator, function (\SplFileInfo $file): bool {
			return $file->getBasename()[0] !== '.'  // . or .. or .gitignore
				&& in_array($file->getExtension(), $this->acceptFiles, true);
		});
	}


	protected function getCommonFilesPath(array $files): string
	{
		$path = reset($files);
		for ($i = 0; $i < strlen($path); $i++) {
			foreach ($files as $file) {
				if (!isset($file[$i]) || $path[$i] !== $file[$i]) {
					$path = substr($path, 0, $i);
					break 2;
				}
			}
		}

		return is_dir($path) ? $path : dirname($path);
	}


	abstract protected function renderSelf();
}
