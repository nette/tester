<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner\Interpreters;


/**
 * Zend PHP CLI executable.
 */
class ZendPhpCliInterpreter extends AbstractInterpreter
{

	public function __construct($path, $args = NULL)
	{
		parent::__construct($path, ' -n' . $args);
	}

}
