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

	public function __construct($path, array $args = [])
	{
		parent::__construct($path, array_merge(['-n'], $args));
	}

}
