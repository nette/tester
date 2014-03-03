<?php

/**
 * This file is part of the Nette Tester.
 *
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Tester\Runner;

/**
 * PHP executable command-line - base interface.
 *
 * @author Michael Moravec
 */
interface IPhpInterpreter
{

	/**
	 * @return string
	 */
	public function getCommandLine();


	/**
	 * @return string
	 */
	public function getVersion();


	/**
	 * @return bool
	 */
	public function isCgi();


	/**
	 * @return string
	 */
	public function getArguments();


	/**
	 * @param string
	 * @param mixed
	 */
	public function addArgument($name, $value = NULL);

}
