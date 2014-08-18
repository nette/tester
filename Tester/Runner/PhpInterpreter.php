<?php

namespace Tester\Runner;

/**
 * @author Michael Moravec
 */
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
}
