<?php

/**
 * This file is part of the Nette Tester.
 *
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */



/**
 * Single test case.
 *
 * @author     David Grudl
 */
class TestCase
{

	public function run()
	{
		$rc = new \ReflectionClass($this);
		foreach ($rc->getMethods() as $method) {
			if (preg_match('#^test[A-Z]#', $method->getName())) {
				$this->runTest($method->getName());
			}
		}
	}



	public function runTest($method)
	{
		$this->setUp();
		try {
			$this->$method();
		} catch (\Exception $e) {
		}
		$this->tearDown();
		if (isset($e)) {
			throw $e;
		}
	}



	protected function setUp()
	{
	}



	protected function tearDown()
	{
	}

}
