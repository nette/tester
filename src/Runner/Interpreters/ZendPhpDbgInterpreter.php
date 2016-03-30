<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner\Interpreters;


/**
 * Zend phpdbg command-line executable with CLI SAPI emulation.
 */
class ZendPhpDbgInterpreter extends AbstractInterpreter
{

	public function __construct($path, $args = NULL)
	{
		parent::__construct($path, ' -qrrb -S cli -n' . $args);

		if (version_compare($this->info->version, '7.0.0', '<')) {
			throw new \Exception('Unable to use phpdbg on PHP < 7.0.0.');
		}
	}


	public function canMeasureCodeCoverage()
	{
		return TRUE;
	}


	public function getShortInfo()
	{
		return parent::getShortInfo() . "; PHPDBG {$this->info->phpDbgVersion}";
	}

}
