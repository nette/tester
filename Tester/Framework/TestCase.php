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

			$exception = isset($info['expectedexception']) ? $info['expectedexception'] : NULL;
			$exceptionMessage = isset($info['expectedexceptionmessage']) ? $info['expectedexceptionmessage'] : NULL;

			foreach ($data as $args) {
				try {
					$this->runTest($method->getName(), $args);

					if ($exception) {
						Assert::fail("Expected exception " . $exception);
					}

				} catch (\Exception $e) {
					if (!$exception) {
						throw $e;
					}
					if (!$e instanceof $exception) {
						Assert::fail('Failed asserting that ' . get_class($e) . " is an instance of class $exception", $exception, get_class($e));
					}
					if ($exceptionMessage) {
						Assert::match($exceptionMessage, $e->getMessage());
					}
				}
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
