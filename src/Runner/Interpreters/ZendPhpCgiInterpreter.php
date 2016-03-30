<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner\Interpreters;


/**
 * Zend PHP CGI executable.
 */
class ZendPhpCgiInterpreter extends AbstractInterpreter
{

	public function __construct($path, $args = NULL)
	{
		parent::__construct($path, ' -n' . $args);
	}


	public function isCgi()
	{
		return TRUE;
	}


	public function getStartupError()
	{
		if ($error = parent::getStartupError()) {
			$error .= "\n(note that PHP CLI generates better error messages)";
		}

		return $error;
	}

}
