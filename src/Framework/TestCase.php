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
	public function run()
	{
		if (func_num_args()) {
			throw new \LogicException('Calling TestCase::run($method) is deprecated. Use TestCase::runTest($method) instead.');
		}

		$methods = array_values(preg_grep(self::METHOD_PATTERN, array_map(function (\ReflectionMethod $rm) {
			return $rm->getName();
		}, (new \ReflectionObject($this))->getMethods())));

		if (isset($_SERVER['argv']) && ($tmp = preg_filter('#--method=([\w-]+)$#Ai', '$1', $_SERVER['argv']))) {
			$method = reset($tmp);
			if ($method === self::LIST_METHODS) {
				Environment::$checkAssertions = FALSE;
				header('Content-Type: text/plain');
				echo '[' . implode(',', $methods) . ']';
				return;
			}
			$this->runTest($method);

		} else {
			foreach ($methods as $method) {
				$this->runTest($method);
			}
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
		if (!method_exists($this, $method)) {
			throw new TestCaseException("Method '$method' does not exist.");
		} elseif (!preg_match(self::METHOD_PATTERN, $method)) {
			throw new TestCaseException("Method '$method' is not a testing method.");
		}

		$method = new \ReflectionMethod($this, $method);
		if (!$method->isPublic()) {
			throw new TestCaseException("Method {$method->getName()} is not public. Make it public or rename it.");
		}

		$info = Helpers::parseDocComment($method->getDocComment()) + ['dataprovider' => NULL, 'throws' => NULL];

		if ($info['throws'] === '') {
			throw new TestCaseException("Missing class name in @throws annotation for {$method->getName()}().");
		} elseif (is_array($info['throws'])) {
			throw new TestCaseException("Annotation @throws for {$method->getName()}() can be specified only once.");
		} else {
			$throws = preg_split('#\s+#', $info['throws'], 2) + [NULL, NULL];
		}

		$data = [];
		if ($args === NULL) {
			$defaultParams = [];
			foreach ($method->getParameters() as $param) {
				$defaultParams[$param->getName()] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : NULL;
			}

			foreach ((array) $info['dataprovider'] as $provider) {
				$res = $this->getData($provider);
				if (!is_array($res) && !$res instanceof \Traversable) {
					throw new TestCaseException("Data provider $provider() doesn't return array or Traversable.");
				}
				foreach ($res as $setName => $set) {
					$set = is_string(key($set)) ? array_merge($defaultParams, $set) : $set;
					if (is_string($setName)) {
						$data[$setName] = $set;
					} else {
						$data[] = $set;
					}
				}
			}

			if (!$info['dataprovider']) {
				if ($method->getNumberOfRequiredParameters()) {
					throw new TestCaseException("Method {$method->getName()}() has arguments, but @dataProvider is missing.");
				}
				$data[] = [];
			}
		} else {
			$data[] = $args;
		}


		if ($this->prevErrorHandler === FALSE) {
			$this->prevErrorHandler = set_error_handler(function ($severity) {
				if ($this->handleErrors && ($severity & error_reporting()) === $severity) {
					$this->handleErrors = FALSE;
					$this->silentTearDown();
				}

				return $this->prevErrorHandler ? call_user_func_array($this->prevErrorHandler, func_get_args()) : FALSE;
			});
		}


		foreach ($data as $setName => $params) {
			try {
				$this->setUp();

				$this->handleErrors = TRUE;
				try {
					if ($info['throws']) {
						$e = Assert::error(function () use ($method, $params) {
							call_user_func_array([$this, $method->getName()], $params);
						}, $throws[0], $throws[1]);
						if ($e instanceof AssertException) {
							throw $e;
						}
					} else {
						call_user_func_array([$this, $method->getName()], $params);
					}
				} catch (\Exception $e) {
					$this->handleErrors = FALSE;
					$this->silentTearDown();
					throw $e;
				}
				$this->handleErrors = FALSE;

				$this->tearDown();

			} catch (AssertException $e) {
				throw $e->setMessage("$e->origMessage in " . (is_string($setName) ? "\"" . $setName . "\": " : "") . "{$method->getName()}(" . (substr(Dumper::toLine($params), 1, -1)) . ')');
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


	private function silentTearDown()
	{
		set_error_handler(function() {});
		try {
			$this->tearDown();
		} catch (\Exception $e) {
		}
		restore_error_handler();
	}

}


class TestCaseException extends \Exception
{
}
