<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner\Output;

use Tester\Runner\OutputHandler;


/**
 * Output handler wrapper. Captures an output to file.
 */
class FileOutput implements OutputHandler
{
	/** @var OutputHandler */
	private $handler;

	/** @var resource */
	private $file;


	public function __construct(OutputHandler $handler, $file)
	{
		$this->handler = $handler;
		$this->file = fopen($file, 'w');
	}


	public function begin()
	{
		ob_start();
		$this->handler->begin();
		fputs($this->file, ob_get_clean());
	}


	public function result($testName, $result, $message)
	{
		ob_start();
		$this->handler->result($testName, $result, $message);
		fputs($this->file, ob_get_clean());
	}


	public function end()
	{
		ob_start();
		$this->handler->end();
		fputs($this->file, ob_get_clean());
	}

}
