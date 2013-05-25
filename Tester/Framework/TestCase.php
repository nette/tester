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
	/** @var string */
	protected $expectedException;

	/** @var string */
	protected $expectedExceptionMessage;



	/**
	 * Runs the test case.
	 * @return void
	 */
	public function run($method = NULL)
	{
		$rc = new \ReflectionClass($this);
		$methods = $method ? array($rc->getMethod($method)) : $rc->getMethods(\ReflectionMethod::IS_PUBLIC);
		foreach ($methods as $method) {
			if (!preg_match('#^test[A-Z]#', $method->getName())) {
				continue;
			}

			$data = array();
			$info = Helpers::parseDocComment($method->getDocComment()) + array('dataprovider' => NULL);
			foreach ((array) $info['dataprovider'] as $provider) {
				$res = $this->getData($provider);
				if (!is_array($res)) {
					throw new TestCaseException("Data provider $provider() doesn't return array.");
				}
				$data = array_merge($data, $res);
			}
			if (!$info['dataprovider']) {
				if ($method->getNumberOfRequiredParameters()) {
					throw new TestCaseException("Method {$method->getName()}() has arguments, but @dataProvider is missing.");
				}
				$data[] = array();
			}

			foreach ($data as $args) {
				$this->runTest($method->getName(), $args);
			}
		}
	}



	/**
	 * Sets expected exception.
	 * @param  string exception class
	 * @param  string exception message
	 */
	public function setExpectedException($class, $message = '')
	{
		$this->expectedException = $class;
		$this->expectedExceptionMessage = $message;
	}



	/**
	 * Runs the single test.
	 * @param  string method name
	 * @param  array arguments
	 * @return void
	 */
	public function runTest($name, array $args = array())
	{
		$this->expectedException = NULL;
		$this->expectedExceptionMessage = NULL;
		$e = NULL;

		$this->setUp();
		try {
			call_user_func_array(array($this, $name), $args);
		} catch (\Exception $e) {
		}
		$this->tearDown();

		if ($this->expectedException) {
			Assert::exception(function() use($e) {
				if ($e instanceof \Exception) {
					throw $e;
				}
			}, $this->expectedException, $this->expectedExceptionMessage);
		} elseif ($e) {
			throw $e;
		}
	}



	/**
	 * @return array
	 */
	protected function getData($provider)
	{
		if (strpos($provider, '.')) {
			list($file, $query) = preg_split('#\s*,?\s+#', "$provider ", 2);
			$rc = new \ReflectionClass($this);
			return DataProvider::load(dirname($rc->getFileName()) . '/' . $file, $query);
		} else {
			return $this->$provider();
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
