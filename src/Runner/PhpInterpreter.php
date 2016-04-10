<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner;


interface PhpInterpreter
{

	/**
	 * @param string
	 * @param string
	 */
	function addPhpIniOption($name, $value = NULL);

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
	function canMeasureCodeCoverage();

	/**
	 * @return bool
	 */
	function isCgi();

	/**
	 * @return string
	 */
	function getStartupError();

}
