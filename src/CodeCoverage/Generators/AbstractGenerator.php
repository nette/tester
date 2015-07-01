<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\CodeCoverage\Generators;


/**
 * Code coverage report generator.
 */
abstract class AbstractGenerator
{
	const
		CODE_DEAD = -2,
		CODE_UNTESTED = -1,
		CODE_TESTED = 1;

	/** @var array */
	public $acceptFiles = array('php', 'phpc', 'phpt', 'phtml');

	/** @var array */
	protected $data;

	/** @var string */
	protected $source;


	/**
	 * @param  string  path to coverage.dat file
	 * @param  string  path to covered source file or directory
	 */
	public function __construct($file, $source = NULL)
	{
		if (!is_file($file)) {
			throw new \Exception("File '$file' is missing.");
		}

		$this->data = @unserialize(file_get_contents($file)); // @ is escalated to exception
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
	}


	public function render($file = NULL)
	{
		$handle = $file ? @fopen($file, 'w') : STDOUT; // @ is escalated to exception
		if (!$handle) {
			throw new \Exception("Unable to write to file '$file'.");
		}

		ob_start(function ($buffer) use ($handle) { fwrite($handle, $buffer); }, 4096);
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


	/**
	 * @return AcceptIterator
	 */
	protected function getSourceIterator()
	{
		$iterator = is_dir($this->source)
			? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->source))
			: new \ArrayIterator(array(new \SplFileInfo($this->source)));

		return new AcceptIterator($iterator, $this->acceptFiles);
	}


	abstract protected function renderSelf();

}


/** @internal */
class AcceptIterator extends \FilterIterator
{
	private $acceptFiles;

	public function __construct(\Iterator $iterator, array $acceptFiles)
	{
		parent::__construct($iterator);
		$this->acceptFiles = $acceptFiles;
	}


	public function accept()
	{
		return substr($this->current()->getBasename(), 0, 1) !== '.'  // . or .. or .gitignore
			&& in_array(pathinfo($this->current(), PATHINFO_EXTENSION), $this->acceptFiles, TRUE);
	}

}
