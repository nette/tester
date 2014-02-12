<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester;


/**
 * Single test case.
 *
 * @author     David Grudl
 */
class TestCase
{
	/** @internal */
	const LIST_METHODS = 'nette-tester-list-methods',
		METHOD_PATTERN = '#^test[A-Z0-9_]#';


	/**
	 * Runs the test case.
	 * @return void
	 */
	public function run($method = NULL)
	{
		if (($method === NULL || $method === self::LIST_METHODS) && isset($_SERVER['argv'][1])) {
			if ($_SERVER['argv'][1] === self::LIST_METHODS) {
				echo json_encode(array_values(preg_grep(self::METHOD_PATTERN, get_class_methods($this))));
				return;
			}
			$method = $_SERVER['argv'][1];
		}

		$methods = preg_grep(self::METHOD_PATTERN, $method ? array($method) : get_class_methods($this));
		foreach ($methods as $method) {
			$this->runMethod($method);
		}
	}


	/**
	 * Runs the test method.
	 * @return void
	 */
	private function runMethod($method)
	{
		$method = new \ReflectionMethod($this, $method);
		if (!$method->isPublic()) {
			throw new TestCaseException("Method {$method->getName()} is not public. Make it public or rename it.");
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

		foreach ($data as $args) {
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
		try {
			$this->tearDown();
		} catch (\Exception $tearDownEx) {
			throw isset($e) ? $e : $tearDownEx;
		}
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
			$rc = new \ReflectionClass($this);
			list($file, $query) = DataProvider::parseAnnotation($provider, $rc->getFileName());
			return DataProvider::load($file, $query);
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
