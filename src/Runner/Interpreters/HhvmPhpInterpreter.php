<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner\Interpreters;


/**
 * HHVM command-line executable.
 */
class HhvmPhpInterpreter extends AbstractInterpreter
{

	public function __construct($path, $args = NULL)
	{
		parent::__construct($path, ' --php -n -d hhvm.log.always_log_unhandled_exceptions=false' . $args); // HHVM issue #3019

		if (version_compare($this->info->hhvmVersion, '3.3.0', '<')) {
			throw new \Exception('HHVM below version 3.3.0 is not supported.');
		}
	}


	public function canMeasureCodeCoverage()
	{
		return FALSE;
	}


	public function getShortInfo()
	{
		return parent::getShortInfo() . "; HHVM {$this->info->hhvmVersion}";
	}

}
