<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester;


/**
 * Runner of TestCase.
 */
class TestCaseRunner
{
	private const LIST_METHODS = 'nette-tester-list-methods';

	/** @var array */
	private $classes = [];


	public function findTests(string $fileMask): self
	{
		$files = [];
		foreach (glob($fileMask) as $file) {
			require_once $file;
			$files[realpath($file)] = true;
		}
		foreach (get_declared_classes() as $class) {
			$rc = new \ReflectionClass($class);
			if ($rc->isSubclassOf(TestCase::class) && isset($files[$rc->getFileName()])) {
				$this->classes[] = $class;
			}
		}
		return $this;
	}


	public function run(): void
	{
		if ($this->runFromCli()) {
			return;
		}

		foreach ($this->classes as $class) {
			$test = $this->createInstance($class);
			$test->run();
		}
	}


	private function runFromCli(): bool
	{
		$args = preg_filter('#--method=([\w:-]+)$#Ai', '$1', $_SERVER['argv'] ?? []);
		$arg = reset($args);
		if (!$arg) {
			return false;

		} elseif ($arg === self::LIST_METHODS) {
			Environment::$checkAssertions = false;
			$methods = [];
			foreach ($this->classes as $class) {
				foreach ($class::findMethods() as $method) {
					$methods[] = $class . '::' . $method;
				}
			}
			header('Content-Type: text/plain');
			echo '[' . implode(',', $methods) . ']';

		} else {
			[$class, $method] = explode('::', $arg);
			$test = $this->createInstance($class);
			$test->runTest($method);
		}
		return true;
	}


	protected function createInstance(string $class): TestCase
	{
		// TOO: can be altered via setFactory(callable)
		return new $class;
	}
}
