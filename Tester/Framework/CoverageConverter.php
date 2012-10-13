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
 * Coverage converter.
 *
 * @author     David Grudl
 * @author     Patrik Votoček
 * @package    Nette\Test
 */
class CoverageConverter
{
	/** @var array */
	private $data;

	/** @var string */
	private $sourceDirectory;

	/** @var string */
	private $name;

	/** @var array */
	private $exclude = array();

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
			die("File '$file' is missing.");
		}

		$this->data = @unserialize(file_get_contents($file));
		if (!$this->data) {
			die("Content of file '$file' is invalid.");
		}

		if (!is_dir($sourceDirectory)) {
			die("Directory '$sourceDirectory' is missing.");
		}

		if (substr($sourceDirectory, -strlen('/')) !== '/') {
			$sourceDirectory .= '/';
		}
		$this->sourceDirectory = $sourceDirectory;
	}



	/**
	 * @param string	application / library name
	 * @return CoverageConverter
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}



	/**
	 * Restricts the search using mask.
	 * Excludes directories from recursive traversing.
	 * @param string|array	exclude path mask
	 * @return CoverageConverter
	 */
	public function addExclude($mask)
	{
		if (is_array($mask)) {
			foreach ($mask as $item) {
				$this->addExclude($item);
			}
		} else {
			if (strncmp($mask, './', strlen('./')) === 0) {
				$mask = $this->sourceDirectory . substr($mask, 2);
			}
			$this->exclude[] = $mask;
		}
		return $this;
	}



	public function renderHtml()
	{
		$this->setupHighlight();
		$this->parse();

		$name = $this->name;
		$classes = self::$classes;
		$files = $this->files;
		$totalSum = $this->totalSum;
		$coveredSum = $this->coveredSum;

		if ($totalSum <= 0) {
			die('No data or 0% coverage');
		}

		include __DIR__ . '/coverage.phtml';
	}



	public function generateHtml($file)
	{
		ini_set('implicit_flush', FALSE);
		ob_start();
		$this->renderHtml();
		$html = ob_get_contents();
		ob_end_clean();
		ini_set('implicit_flush', TRUE);

		if (@file_put_contents($file, $html) === FALSE) {
			$dir = dirname($file);
			die("Directory '$dir' is not writable");
		}
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

		$pattern = NULL;
		if (count($this->exclude)) {
			$pattern = $this->getExcludePattern();
		}

		$this->files = array();

		$iterator = new RecursiveDirectoryIterator($this->sourceDirectory);
		$iterator = new RecursiveCallbackFilterIterator($iterator, function($entry) use($pattern) {
			if (substr($entry->getBasename(), 0, 1) === '.') { // . or .. or .gitignore
				return FALSE;
			} elseif (!is_null($pattern)) {
				return !preg_match($pattern, strtr($entry, '\\', '/'));
			}
			return TRUE;
		});
		$iterator = new RecursiveIteratorIterator($iterator);
		foreach ($iterator as $entry) {
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



	/**
	 * Returns exclude patterns to regular expression.
	 * @return string
	 */
	private function getExcludePattern()
	{
		$pattern = array();
		// TODO: accept regexp
		foreach ($this->exclude as $mask) {
			$mask = rtrim(strtr($mask, '\\', '/'), '/');
			$prefix = '';
			if ($mask === '') {
				continue;

			} elseif ($mask === '*') {
				return NULL;

			} elseif ($mask[0] === '/') { // absolute fixing
				$mask = ltrim($mask, '/');
				$prefix = '(?<=^/)';

			}

			$pattern[] = $prefix . strtr(preg_quote($mask, '#'),
				array('\*\*' => '.*', '\*' => '[^/]*', '\?' => '[^/]', '\[\!' => '[^', '\[' => '[', '\]' => ']', '\-' => '-')
			);
		}

		return $pattern ? '#/(' . implode('|', $pattern) . ')$#i' : NULL;
	}
}
