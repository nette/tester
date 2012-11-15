<?php

/**
 * This file is part of the Nette Tester.
 *
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Tester;



/**
 * Single test case.
 *
 * @author     David Grudl
 */
class TestCase
{

	/**
	 * Runs the test case.
	 * @return void
	 */
	public function run()
	{
		$rc = new \ReflectionClass($this);
		foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
			if (preg_match('#^test[A-Z]#', $method->getName())) {
				$this->runTest($method->getName());
			}
		}
	}



	/**
	 * Runs the single test.
	 * @return void
	 */
	public function runTest($name, array $args = array())
	{
		$this->setUp();
		try {
			call_user_func_array(array($this, $name), $args);
		} catch (\Exception $e) {
		}
		$this->tearDown();
		if (isset($e)) {
			throw $e;
		}
	}



	/**
	 * This method is called before a test is executed.
	 * @return void
	 */
	protected function setUp()
	{
	}



	/**
	 * This method is called after a test is executed.
	 * @return void
	 */
	protected function tearDown()
	{
	}

}
