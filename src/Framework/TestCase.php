<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester;


/**
 * Single test case.
 */
class TestCase
{
	/** @internal */
	public const
		LIST_METHODS = 'nette-tester-list-methods',
		METHOD_PATTERN = '#^test[A-Z0-9_]#';

	/** @var bool */
	private $handleErrors = false;

	/** @var callable|false|null */
	private $prevErrorHandler = false;


	/**
	 * Runs the test case.
	 */
	public function run(): void
	{
		if (func_num_args()) {
			throw new \LogicException('Calling TestCase::run($method) is deprecated. Use TestCase::runTest($method) instead.');
		}

		$methods = array_values(preg_grep(self::METHOD_PATTERN, array_map(function (\ReflectionMethod $rm): string {
			return $rm->getName();
		}, (new \ReflectionObject($this))->getMethods())));

		if (isset($_SERVER['argv']) && ($tmp = preg_filter('#--method=([\w-]+)$#Ai', '$1', $_SERVER['argv']))) {
			$method = reset($tmp);
			if ($method === self::LIST_METHODS) {
				$this->sendMethodList($methods);
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
	 * @param  array  $args  test method parameters (dataprovider bypass)
	 */
	public function runTest(string $method, array $args = null): void
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

		$info = Helpers::parseDocComment((string) $method->getDocComment()) + ['dataprovider' => null, 'throws' => null];

		if ($info['throws'] === '') {
			throw new TestCaseException("Missing class name in @throws annotation for {$method->getName()}().");
		} elseif (is_array($info['throws'])) {
			throw new TestCaseException("Annotation @throws for {$method->getName()}() can be specified only once.");
		} else {
			$throws = is_string($info['throws']) ? preg_split('#\s+#', $info['throws'], 2) : [];
		}

		$data = [];
		if ($args === null) {
			$defaultParams = [];
			foreach ($method->getParameters() as $param) {
				$defaultParams[$param->getName()] = $param->isDefaultValueAvailable()
					? $param->getDefaultValue()
					: null;
			}

			foreach ((array) $info['dataprovider'] as $i => $provider) {
				$res = $this->getData($provider);
				if (!is_array($res) && !$res instanceof \Traversable) {
					throw new TestCaseException("Data provider $provider() doesn't return array or Traversable.");
				}

				foreach ($res as $k => $set) {
					$data["$i-$k"] = is_string(key($set))
						? array_merge($defaultParams, $set)
						: $set;
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


		if ($this->prevErrorHandler === false) {
			$this->prevErrorHandler = set_error_handler(function (int $severity): ?bool {
				if ($this->handleErrors && ($severity & error_reporting()) === $severity) {
					$this->handleErrors = false;
					$this->silentTearDown();
				}

				return $this->prevErrorHandler
					? ($this->prevErrorHandler)(...func_get_args())
					: false;
			});
		}


		foreach ($data as $k => $params) {
			try {
				$this->setUp();

				$this->handleErrors = true;
				$params = array_values($params);
				try {
					if ($info['throws']) {
						$e = Assert::error(function () use ($method, $params): void {
							[$this, $method->getName()](...$params);
						}, ...$throws);
						if ($e instanceof AssertException) {
							throw $e;
						}
					} else {
						[$this, $method->getName()](...$params);
					}
				} catch (\Exception $e) {
					$this->handleErrors = false;
					$this->silentTearDown();
					throw $e;
				}
				$this->handleErrors = false;

				$this->tearDown();

			} catch (AssertException $e) {
				throw $e->setMessage(sprintf(
					'%s in %s(%s)%s',
					$e->origMessage,
					$method->getName(),
					substr(Dumper::toLine($params), 1, -1),
					is_string($k) ? (" (data set '" . explode('-', $k, 2)[1] . "')") : ''
				));
			}
		}
	}


	/**
	 * @return mixed
	 */
	protected function getData(string $provider)
	{
		if (strpos($provider, '.') === false) {
			return $this->$provider();
		} else {
			$rc = new \ReflectionClass($this);
			[$file, $query] = DataProvider::parseAnnotation($provider, $rc->getFileName());
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


	private function silentTearDown(): void
	{
		set_error_handler(function () {});
		try {
			$this->tearDown();
		} catch (\Exception $e) {
		}
		restore_error_handler();
	}


	private function sendMethodList(array $methods): void
	{
		Environment::$checkAssertions = false;
		header('Content-Type: text/plain');
		echo "\n";
		echo 'TestCase:' . static::class . "\n";
		echo 'Method:' . implode("\nMethod:", $methods) . "\n";

		$dependentFiles = [];
		$reflections = [new \ReflectionObject($this)];
		while (count($reflections)) {
			$rc = array_shift($reflections);
			$dependentFiles[$rc->getFileName()] = 1;

			if ($rpc = $rc->getParentClass()) {
				$reflections[] = $rpc;
			}

			foreach ($rc->getTraits() as $rt) {
				$reflections[] = $rt;
			}
		}
		echo 'Dependency:' . implode("\nDependency:", array_keys($dependentFiles)) . "\n";
	}
}


class TestCaseException extends \Exception
{
}
