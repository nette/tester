<?php declare(strict_types=1);

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Attributes;


/**
 * Supplies argument sets to a TestCase test method, either from another method or from a data file.
 * $provider is a method name or a file path; $query filters data-set keys of a file provider.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class DataProvider
{
	public function __construct(
		public string $provider,
		public string $query = '',
	) {
	}
}
