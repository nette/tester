<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester;


/**
 * Single test case.
 */
class TestCase
{
	/** @internal */
	const LIST_METHODS = 'nette-tester-list-methods',
		METHOD_PATTERN = '#^test[A-Z0-9_]#';

	/** @var array */
	public $onBeforeSetUp;

	/** @var array */
	public $onAfterSetUp;

	/** @var array */
	public $onBeforeTearDown;

	/** @var array */
	public $onAfterTearDown;

	/** @var array */
	public $onBeforeRunTest;

	/** @var array */
	public $onAfterRunTest;

	/** @var array */
	public $onBeforeRun;

	/** @var array */
	public $onAfterRun;

	private function onEvent($name, $testName = NULL) {
		if (is_array($this->$name) || $this->$name instanceof \Traversable) {
			foreach ($this->$name as $index => $handler) {
				if (!is_callable($handler, false)) {
					throw new TestCaseException(sprintf("Event callback $name [$index] is not callable.")
					);
				}
				call_user_func_array($handler, $testName !== NULL ? array($this, $testName) : array($this));
			}
		} elseif ($this->$name !== NULL) {
			throw new TestCaseException(
				"Events " . get_class($this) . "::$$name must be array or NULL, " . gettype($this->$name) . ' given.');
		}
	}

	/**
	 * Runs the test case.
	 * @return void
	 */
	public function run($method = NULL)
	{
		$r = new \ReflectionObject($this);
		$methods = array_values(preg_grep(self::METHOD_PATTERN, array_map(function (\ReflectionMethod $rm) {
			return $rm->getName();
		}, $r->getMethods())));

		if (substr($method, 0, 2) === '--') { // back compatibility
			$method = NULL;
		}

		if ($method === NULL && isset($_SERVER['argv']) && ($tmp = preg_filter('#(--method=)?([\w-]+)$#Ai', '$2', $_SERVER['argv']))) {
			$method = reset($tmp);
			if ($method === self::LIST_METHODS) {
				Environment::$checkAssertions = FALSE;
				header('Content-Type: text/plain');
				echo '[' . implode(',', $methods) . ']';
				return;
			}
		}

		try {
			$this->onEvent("onBeforeRun");
			if ($method === NULL) {
				foreach ($methods as $method) {
					$this->runTest($method);
				}
			} elseif (in_array($method, $methods, TRUE)) {
				$this->runTest($method);
			} else {
				throw new TestCaseException("Method '$method' does not exist or it is not a testing method.");
			}
			$this->onEvent("onAfterRun");
		} catch(\Exception $e) {
			$this->onEvent("onAfterRun");
			throw $e;
		}

	}


	/**
	 * Runs the test method.
	 * @param  string  test method name
	 * @param  array  test method parameters (dataprovider bypass)
	 * @return void
	 */
	public function runTest($method, array $args = NULL)
	{
		$method = new \ReflectionMethod($this, $method);
		if (!$method->isPublic()) {
			throw new TestCaseException("Method {$method->getName()} is not public. Make it public or rename it.");
		}

		$info = Helpers::parseDocComment($method->getDocComment()) + array('dataprovider' => NULL, 'throws' => NULL);

		if ($info['throws'] === '') {
			throw new TestCaseException("Missing class name in @throws annotation for {$method->getName()}().");
		} elseif (is_array($info['throws'])) {
			throw new TestCaseException("Annotation @throws for {$method->getName()}() can be specified only once.");
		} else {
			$throws = preg_split('#\s+#', $info['throws'], 2) + array(NULL, NULL);
		}

		$data = array();
		if ($args === NULL) {
			$defaultParams = array();
			foreach ($method->getParameters() as $param) {
				$defaultParams[$param->getName()] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : NULL;
			}

			foreach ((array) $info['dataprovider'] as $provider) {
				$res = $this->getData($provider);
				if (!is_array($res)) {
					throw new TestCaseException("Data provider $provider() doesn't return array.");
				}
				foreach ($res as $set) {
					$data[] = is_string(key($set)) ? array_merge($defaultParams, $set) : $set;
				}
			}

			if (!$info['dataprovider']) {
				if ($method->getNumberOfRequiredParameters()) {
					throw new TestCaseException("Method {$method->getName()}() has arguments, but @dataProvider is missing.");
				}
				$data[] = array();
			}
		} else {
			$data[] = $args;
		}

		$this->onEvent("onBeforeRunTest", $method->getName());
		foreach ($data as $params) {
			try {
				$this->onEvent("onBeforeSetUp", $method->getName());
				$this->setUp();
				$this->onEvent("onAfterSetUp", $method->getName());

				try {
					if ($info['throws']) {
						$tmp = $this;
						$e = Assert::error(function () use ($tmp, $method, $params) {
							call_user_func_array(array($tmp, $method->getName()), $params);
						}, $throws[0], $throws[1]);
						if ($e instanceof AssertException) {
							throw $e;
						}
					} else {
						call_user_func_array(array($this, $method->getName()), $params);
					}
				} catch (\Exception $testException) {
				}

				try {
					$this->onEvent("onBeforeTearDown", $method->getName());
					$this->tearDown();
					$this->onEvent("onAfterTearDown", $method->getName());
				} catch (\Exception $tearDownException) {
				}

				if (isset($testException)) {
					throw $testException;
				} elseif (isset($tearDownException)) {
					throw $tearDownException;
				}

			} catch (AssertException $e) {
				$this->onEvent("onAfterRunTest", $method->getName());
				throw $e->setMessage("$e->origMessage in {$method->getName()}" . (substr(Dumper::toLine($params), 5)));
			}
		}
		$this->onEvent("onAfterRunTest", $method->getName());
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
