<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester\Runner;


/**
 * Runner output.
 * @method void jobStarted(Job $job) called when a job starts running
 * @method void tick(Job[] $running) called periodically during test execution
 */
interface OutputHandler
{
	function begin(): void;

	function prepare(Test $test): void;

	function finish(Test $test): void;

	function end(): void;
}
