<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner;


interface PhpInterpreter
{

	/**
	 * @return string
	 */
	function getCommandLine();

	/**
	 * @return string
	 */
	function getVersion();

	/**
	 * @return bool
	 */
	function hasXdebug();

	/**
	 * @return bool
	 */
	function isCgi();

	/**
	 * @return string
	 */
	function getErrorOutput();

}
