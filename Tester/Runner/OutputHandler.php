<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester;


/**
 * Runner output.
 */
interface OutputHandler
{

	function begin();

	function result($testName, $result, $message);

	function end();

}
