<?php declare(strict_types=1);

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner;


/**
 * Signals that the test run was interrupted by the user (e.g. Ctrl+C).
 */
class InterruptException extends \Exception
{
}
