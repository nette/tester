<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester;


/**
 * Runner output.
 */
interface OutputHandler
{
	function begin();

	function prepare(Test $test);

	function finish(Test $test);

	function end();
}
