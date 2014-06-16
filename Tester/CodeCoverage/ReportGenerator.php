<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\CodeCoverage;


/**
 * Code coverage report generator.
 */
class ReportGenerator
{
	/** @var array */
	public $acceptFiles = array('php', 'phpc', 'phpt', 'phtml');

	/** @var array */
	private $data;

	/** @var string */
	private $source;

	/** @var string */
	private $title;

	/** @var array */
	private $files = array();

	/** @var int */
	private $totalSum = 0;

	/** @var int */
	private $coveredSum = 0;

	/** @var array */
	public static $classes = array(
		1 => 't', // tested
		-1 => 'u', // untested
		-2 => 'dead', // dead code
	);


	/**
	 * @param string  path to coverage.dat file
	 * @param string  path to source file/directory
	 */
	public function __construct($file, $source, $title = NULL)
	{
		if (!is_file($file)) {
			throw new \Exception("File '$file' is missing.");
		}

		$this->data = @unserialize(file_get_contents($file));
		if (!is_array($this->data)) {
			throw new \Exception("Content of file '$file' is invalid.");
		}

		if (!$source) {
			$source = key($this->data);
			for ($i = 0; $i < strlen($source); $i++) {
				foreach ($this->data as $s => $foo) {
					if (!isset($s[$i]) || $source[$i] !== $s[$i]) {
						$source = substr($source, 0, $i);
						break 2;
					}
				}
			}
			$source = dirname($source . 'x');

		} elseif (!file_exists($source)) {
			throw new \Exception("File or directory '$source' is missing.");
		}

		$this->source = realpath($source);
		$this->title = $title;
	}


	public function render($file = NULL)
	{
		$this->setupHighlight();
		$this->parse();

		$title = $this->title;
		$classes = self::$classes;
		$files = $this->files;
		$totalSum = $this->totalSum;
		$coveredSum = $this->coveredSum;

		$handle = $file ? @fopen($file, 'w') : STDOUT;
		if (!$handle) {
			throw new \Exception("Unable to write to file '$file'.");
		}
		ob_start(function($buffer) use ($handle) { fwrite($handle, $buffer); }, 4096);
		include __DIR__ . '/template.phtml';
		ob_end_flush();
	}


	private function setupHighlight()
	{
		ini_set('highlight.comment', '#999; font-style: italic');
		ini_set('highlight.default', '#000');
		ini_set('highlight.html', '#06B');
		ini_set('highlight.keyword', '#D24; font-weight: bold');
		ini_set('highlight.string', '#080');
	}


	private function parse()
	{
		if (count($this->files) > 0) {
			return;
		}

		$entries = is_dir($this->source)
			? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->source))
			: array(new \SplFileInfo($this->source));

		$this->files = array();
		foreach ($entries as $entry) {
			if (substr($entry->getBasename(), 0, 1) === '.'  // . or .. or .gitignore
				|| !in_array(pathinfo($entry, PATHINFO_EXTENSION), $this->acceptFiles, TRUE))
			{
				continue;
			}
			$entry = (string) $entry;

			$coverage = $covered = $total = 0;
			$loaded = isset($this->data[$entry]);
			$lines = array();
			if ($loaded) {
				$lines = $this->data[$entry];
				foreach ($lines as $flag) {
					if ($flag >= -1) {
						$total++;
					}
					if ($flag >= 1) {
						$covered++;
					}
				}
				$coverage = round($covered * 100 / $total);
				$this->totalSum += $total;
				$this->coveredSum += $covered;
			}

			$light = $total ? $total < 5 : count(file($entry)) < 50;
			$this->files[] = (object) array(
				'name' => str_replace((is_dir($this->source) ? $this->source : dirname($this->source)) . DIRECTORY_SEPARATOR, '', $entry),
				'file' => $entry,
				'lines' => $lines,
				'coverage' => $coverage,
				'total' => $total,
				'class' => $light ? 'light' : ($loaded ? NULL : 'not-loaded'),
			);
		}
	}
}
