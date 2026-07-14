<?php declare(strict_types=1);

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Attributes;


/**
 * Declares that a TestCase test method must throw the given exception or trigger a PHP error.
 * The class is an exception class-string or an E_* constant; the message is an optional pattern.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class Throws
{
	public function __construct(
		public string|int $class,
		public ?string $message = null,
	) {
	}
}
