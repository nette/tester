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

			$traceRecord = array(
				'file' => $method->getFileName(),
				'line' => $method->getStartLine(),
				'function' => $method->getName(),
				'class' => get_class($this),
				'type' => '->',
			);

			$data = array();
			$info = Helpers::parseDocComment($method->getDocComment()) + array('dataprovider' => NULL, 'throws' => NULL);
			if ($info['throws'] === TRUE) {
				throw Helpers::appendTrace(new TestCaseException('Missing class name in @throws annotation.'), $traceRecord);

			} elseif (is_array($info['throws'])) {
				throw Helpers::appendTrace(new TestCaseException('Cannot specify @throws annotation more then once.'), $traceRecord);
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
					throw Helpers::appendTrace(new TestCaseException('Method has arguments, but @dataProvider is missing.'), $traceRecord);
				}
				$data[] = array();
			}

			list($throwsClass, $throwsMessage) = preg_split('#\s+#', $info['throws'], 2) + array(NULL, NULL);

			foreach ($data as $key => $args) {
				$e = NULL;
				try {
					$this->runTest($method->getName(), $args);
				} catch (AssertException $e) {
					throw $e;
				} catch (\Exception $e) {
				}

				if ($info['throws'] === NULL) {
					if ($e) {
						throw $e;
					}

				} else {
					try {
						Assert::exception(function() use ($e) {
							if ($e) {
								throw $e;
							}
						}, $throwsClass, $throwsMessage);

					} catch (AssertException $ae) {
						throw Helpers::appendTrace($ae, $traceRecord + array('line' => $method->getEndLine(), 'args' => $args));
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
