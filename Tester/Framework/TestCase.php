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

	public function run()
	{
		$rc = new \ReflectionClass($this);
		foreach ($rc->getMethods() as $method) {
			if (preg_match('#^test[A-Z]#', $method->getName())) {
				$this->runTest($method->getName());
			}
		}
	}



	/**
	 * @param string	test method name
	 */
	public function runTest($method)
	{
		$ref = new \ReflectionMethod($this, $method);
		$doc = $ref->getDocComment();
		if (preg_match_all('~@dataProvider\s(?P<method>[a-z0-9_]+)~i', $doc, $dataProvider)) {
			if (isset($dataProvider['method'])) {
				$class = $ref->getDeclaringClass()->getName();

				if (count($dataProvider['method']) > 1) {
					throw new \RuntimeException("Multiple data provider detected at '$class::$method'");
				}

				$data = $this->{$dataProvider['method'][0]}();
				if (!is_array($data)) {
					throw new \RuntimeException("Data provider '$class::{$dataProvider['method'][0]}' returns invalid data");
				}

				foreach ($data as $args) {
					if (!is_array($args)) {
						$args = array($args);
					}

					$this->processTest($method, $args);
				}

				return;
			}
		}

		$this->processTest($method);
	}

	/**
	 * @param string	test method name
	 * @param array		args
	 */
	protected function processTest($method, array $args = array())
	{
		$this->setUp();
		try {
			call_user_func_array(array($this, $method), $args);
		} catch (\Exception $e) {
		}
		$this->tearDown();
		if (isset($e)) {
			throw $e;
		}
	}



	protected function setUp()
	{
	}



	protected function tearDown()
	{
	}

}
