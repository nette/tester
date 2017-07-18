<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\CodeCoverage\Generators;


/**
 * Code coverage report generator.
 */
class HtmlGenerator extends AbstractGenerator
{
	/** @var array */
	public static $classes = [
		self::CODE_TESTED => 't', // tested
		self::CODE_UNTESTED => 'u', // untested
		self::CODE_DEAD => 'dead', // dead code
	];

	/** @var string */
	private $title;

	/** @var array */
	private $files = [];


	/**
	 * @param  string  path to coverage.dat file
	 * @param  string  path to source file/directory
	 * @param  string
	 */
	public function __construct($file, $source = null, $title = null)
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
		$coveredPercent = $this->getCoveredPercent();

		include __DIR__ . '/template.phtml';
	}


	private function setupHighlight()
	{
		ini_set('highlight.comment', 'hc');
		ini_set('highlight.default', 'hd');
		ini_set('highlight.html', 'hh');
		ini_set('highlight.keyword', 'hk');
		ini_set('highlight.string', 'hs');
	}


	private function parse()
	{
		if (count($this->files) > 0) {
			return;
		}

		$this->files = [];
		foreach ($this->getSourceIterator() as $entry) {
			$entry = (string) $entry;

			$coverage = $covered = $total = 0;
			$loaded = !empty($this->data[$entry]);
			$lines = [];
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
			} else {
				$this->totalSum += count(file($entry, FILE_SKIP_EMPTY_LINES));
			}

			$light = $total ? $total < 5 : count(file($entry)) < 50;
			$this->files[] = (object) [
				'name' => str_replace((is_dir($this->source) ? $this->source : dirname($this->source)) . DIRECTORY_SEPARATOR, '', $entry),
				'file' => $entry,
				'lines' => $lines,
				'coverage' => $coverage,
				'total' => $total,
				'class' => $light ? 'light' : ($loaded ? null : 'not-loaded'),
			];
		}
	}
}
