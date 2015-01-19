<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\CodeCoverage\Generators;


/**
 * Code coverage report generator.
 */
class HtmlGenerator extends AbstractGenerator
{
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
		self::CODE_TESTED => 't', // tested
		self::CODE_UNTESTED => 'u', // untested
		self::CODE_DEAD => 'dead', // dead code
	);


	/**
	 * @param  string  path to coverage.dat file
	 * @param  string  path to source file/directory
	 * @param  string
	 */
	public function __construct($file, $source = NULL, $title = NULL)
	{
		parent::__construct($file, $source);
		$this->title = $title;
	}


	protected function renderSelf()
	{
		$this->setupHighlight();
		$this->parse();

		$title = $this->title;
		$classes = self::$classes;
		$files = $this->files;
		$totalSum = $this->totalSum;
		$coveredSum = $this->coveredSum;

		include __DIR__ . '/template.phtml';
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
		foreach ($this->getSourceIterator() as $entry) {
			$entry = (string) $entry;

			$coverage = $covered = $total = 0;
			$loaded = isset($this->data[$entry]);
			$lines = array();
			if ($loaded) {
				$lines = $this->data[$entry];
				foreach ($lines as $flag) {
					if ($flag >= self::CODE_UNTESTED) {
						$total++;
					}
					if ($flag >= self::CODE_TESTED) {
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
