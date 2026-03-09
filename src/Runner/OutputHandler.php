<?php declare(strict_types=1);

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner;


/**
 * Receives test lifecycle events from the runner to produce output.
 */
interface OutputHandler
{
	function begin(): void;

	function prepare(Test $test): void;

	function finish(Test $test): void;

	function end(): void;
}
