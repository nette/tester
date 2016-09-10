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

	function begin(array $testInstances);

	function result(TestInstance $testInstance);

	function end();

}
