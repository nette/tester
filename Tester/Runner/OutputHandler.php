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

use Tester;


/**
 * Runner output.
 *
 * @author     David Grudl
 */
interface OutputHandler
{

	function begin();

	function result($testName, $result, $message);

	function end();

}
