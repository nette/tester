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
			if (!preg_match('#^test[A-Z]#', $method->getName())) {
				continue;
			}

			$data = array();
			preg_match_all('#@dataProvider\s+(\w+)#i', $method->getDocComment(), $matches);
			if (!$matches[1]) {
				if ($method->getNumberOfRequiredParameters()) {
					throw new TestCaseException("Method {$method->getName()}() has arguments, but @dataProvider is missing.");
				}
				$data[] = array();
			}
			foreach ($matches[1] as $provider) {
				$res = $this->$provider();
				if (!is_array($res)) {
					throw new TestCaseException("Data provider $provider() doesn't return array.");
				}
				$data = array_merge($data, $res);
			}

			foreach ($data as $args) {
				$this->runTest($method->getName(), $args);
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



class TestCaseException extends \Exception
{
}
