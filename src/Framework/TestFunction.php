<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester;


/**
 * test(), beforeEach(), afterEach()
 */
class TestFunction
{
	/** ?\Closure */
	private $before;

	/** ?\Closure */
	private $after;


	public function beforeEach(?\Closure $fn): void
	{
		$this->before = $fn;
	}


	public function afterEach(?\Closure $fn): void
	{
		$this->after = $fn;
	}


	public function test(string $description, \Closure $fn): void
	{
		if ($this->before) {
			($this->before)();
		}

		try {
			$fn();
		} catch (\Throwable $e) {
		}

		if ($description !== '') {
			echo empty($e)
				? Dumper::color('lime', '√')
				: Dumper::color('red', '×');
			echo " $description\n";
		}

		if (isset($e)) {
			throw $e;
		}

		if ($this->after) {
			($this->after)();
		}
	}
}
