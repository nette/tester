<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner;


/**
 * PHP interpreter interface.
 *
 * @author     David Grudl
 * @author     Miloslav Hůla
 */
interface IPhpInterpreter
{
	/**
	 * Get single line human readable info.
	 * @return string
	 */
	function getShortInfo();

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
	 * @param  string
	 * @param  string
	 */
	function setIniValue($name, $value);


	/**
	 * @param  string  PHP script to run path
	 * @param  string  script arguments
	 * @param  array  PHP INI values
	 * @param  array  system environmental variables
	 */
	function run($file, array $arguments, array $iniValues, array $envVars);


	/**
	 * @return bool
	 */
	function isRunning();


	/**
	 * @return array[int exitCode, string stdout]
	 */
	function getResult();

}
