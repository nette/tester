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
			$info = Helpers::parseDocComment($method->getDocComment()) + array('dataprovider' => NULL, 'throws' => NULL);

			if ($info['throws'] === '') {
				throw new TestCaseException("Missing class name in @throws annotation for {$method->getName()}().");
			} elseif (is_array($info['throws'])) {
				throw new TestCaseException("Annotation @throws for {$method->getName()}() can be specified only once.");
			} else {
				$throws = preg_split('#\s+#', $info['throws'], 2) + array(NULL, NULL);
			}

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

			foreach ($data as $key => $args) {
				try {
					if ($info['throws']) {
						$tmp = $this;
						$e = Assert::error(function() use ($tmp, $method, $args) {
							$tmp->runTest($method->getName(), $args);
						}, $throws[0], $throws[1]);
						if ($e instanceof AssertException) {
							throw $e;
						}
					} else {
						$this->runTest($method->getName(), $args);
					}
				} catch (AssertException $e) {
					$e->message .= " in {$method->getName()}" . (substr(Dumper::toLine($args), 5));
					throw $e;
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
			return DataProvider::load(dirname($rc->getFileName()) . DIRECTORY_SEPARATOR . $file, $query);
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
