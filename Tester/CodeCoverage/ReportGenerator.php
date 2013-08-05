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
 * Code coverage report generator.
 *
 * @author     David Grudl
 * @author     Patrik Votoček
 * @package    Nette\Test
 */
class ReportGenerator
{
	/** @var array */
	public $acceptFiles = array('php', 'phpc', 'phpt', 'phtml');

	/** @var array */
	private $data;

	/** @var string */
	private $sourceDir;

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
	 * @param string  path to source files
	 */
	public function __construct($file, $sourceDir, $title = NULL)
	{
		if (!is_file($file)) {
			throw new \Exception("File '$file' is missing.");
		}

		$this->data = @unserialize(file_get_contents($file));
		if (!is_array($this->data)) {
			throw new \Exception("Content of file '$file' is invalid.");
		}

		if (!$sourceDir) {
			$sourceDir = key($this->data);
			for ($i = 0; $i < strlen($sourceDir); $i++) {
				foreach ($this->data as $s => $foo) {
					if (!isset($s[$i]) || $sourceDir[$i] !== $s[$i]) {
						$sourceDir = substr($sourceDir, 0, $i);
						break 2;
					}
				}
			}
			$sourceDir = dirname($sourceDir . 'x');

		} elseif (!is_dir($sourceDir)) {
			throw new \Exception("Directory '$sourceDir' is missing.");
		}

		$this->sourceDir = realpath($sourceDir) . DIRECTORY_SEPARATOR;
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

		$this->files = array();
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->sourceDir)) as $entry) {
			if (substr($entry->getBasename(), 0, 1) === '.'  // . or .. or .gitignore
				|| !in_array(pathinfo($entry, PATHINFO_EXTENSION), $this->acceptFiles))
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
				'name' => str_replace($this->sourceDir, '', $entry),
				'file' => $entry,
				'lines' => $lines,
				'coverage' => $coverage,
				'total' => $total,
				'class' => $light ? 'light' : ($loaded ? NULL : 'not-loaded'),
			);
		}
	}
}
