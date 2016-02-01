<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
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


	/** @var bool */
	private $handleErrors = FALSE;

	/** @var callable|NULL|FALSE */
	private $prevErrorHandler = FALSE;


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

		if ($method === NULL) {
			foreach ($methods as $method) {
				$this->runTest($method);
			}
		} elseif (in_array($method, $methods, TRUE)) {
			$this->runTest($method);
		} else {
			throw new TestCaseException("Method '$method' does not exist or it is not a testing method.");
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
				if (!is_array($res) && !$res instanceof \Traversable) {
					throw new TestCaseException("Data provider $provider() doesn't return array or Traversable.");
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


		if ($this->prevErrorHandler === FALSE) {
			$me = $this;
			$handleErrors = & $this->handleErrors;
			$prev = & $this->prevErrorHandler;

			$prev = set_error_handler(function ($severity) use ($me, & $prev, & $handleErrors) {
				if ($handleErrors && ($severity & error_reporting()) === $severity) {
					$handleErrors = FALSE;
					$rm = new \ReflectionMethod($me, 'tearDown');
					$rm->setAccessible(TRUE);

					set_error_handler(function() {});  // mute all errors
					$rm->invoke($me);
					restore_error_handler();
				}

				return $prev ? call_user_func_array($prev, func_get_args()) : FALSE;
			});
		}


		foreach ($data as $params) {
			try {
				$this->setUp();

				$this->handleErrors = TRUE;
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
				$this->handleErrors = FALSE;

				try {
					$this->tearDown();
				} catch (\Exception $tearDownException) {
				}

				if (isset($testException)) {
					throw $testException;
				} elseif (isset($tearDownException)) {
					throw $tearDownException;
				}

			} catch (AssertException $e) {
				throw $e->setMessage("$e->origMessage in {$method->getName()}" . (substr(Dumper::toLine($params), 5)));
			}
		}
	}


	/**
	 * @return array
	 */
	protected function getData($provider)
	{
		if (strpos($provider, '.') === FALSE) {
			return $this->$provider();
		} else {
			$rc = new \ReflectionClass($this);
			list($file, $query) = DataProvider::parseAnnotation($provider, $rc->getFileName());
			return DataProvider::load($file, $query);
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
