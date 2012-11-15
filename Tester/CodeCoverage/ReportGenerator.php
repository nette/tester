<?php

/**
 * This file is part of the Nette Tester.
 *
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 *
 * @package    Nette\Test
 */



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
	private $data;

	/** @var string */
	private $sourceDirectory;

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
	 * @param string	path to coverage.dat file
	 * @param string	path to source files
	 */
	public function __construct($file, $sourceDirectory)
	{
		if (!is_file($file)) {
			throw new \Exception("File '$file' is missing.");
		}

		$this->data = @unserialize(file_get_contents($file));
		if (!$this->data) {
			throw new \Exception("Content of file '$file' is invalid.");
		}

		if (!is_dir($sourceDirectory)) {
			throw new \Exception("Directory '$sourceDirectory' is missing.");
		}

		$this->sourceDirectory = realpath($sourceDirectory) . DIRECTORY_SEPARATOR;
	}



	public function render($file = NULL)
	{
		$this->setupHighlight();
		$this->parse();

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
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->sourceDirectory)) as $entry) {
			if (substr($entry->getBasename(), 0, 1) === '.') { // . or .. or .gitignore
				continue;
			}
			$entry = (string) $entry;

			$coverage = $covered = $total = 0;
			$lines = array();
			if (isset($this->data[$entry])) {
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

			$this->files[] = (object) array(
				'name' => str_replace($this->sourceDirectory, '', $entry),
				'file' => $entry,
				'lines' => $lines,
				'coverage' => $coverage,
				'total' => $total,
				'light' => $total ? $total < 5 : count(file($entry)) < 50,
			);
		}
	}
}
