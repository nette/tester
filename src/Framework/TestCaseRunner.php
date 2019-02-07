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


	public function run(TestCase $test = null): void
	{
		if ($this->runFromCli($test)) {
			return;

		} elseif ($test) {
			$test->run();

		} else {
			foreach ($this->classes as $class) {
				$test = $this->createInstance($class);
				$test->run();
			}
		}
	}


	private function runFromCli(TestCase $test = null): bool
	{
		$args = preg_filter('#--method=([\w:-]+)$#Ai', '$1', $_SERVER['argv'] ?? []);
		$arg = reset($args);

		if ($arg) {
			[$class, $method] = explode('::', $arg);
			$test = $test ?: $this->createInstance($class);
			$test->runTest($method);
			return true;

		} elseif (getenv(Environment::RUNNER)) {
			Environment::$checkAssertions = false;
			$methods = [];
			$classes = $test ? [get_class($test)] : $this->classes;
			foreach ($classes as $class) {
				foreach ($class::findMethods() as $method) {
					$methods[] = $class . '::' . $method;
				}
			}
			header('Content-Type: text/plain');
			echo '[' . implode(',', $methods) . ']';
			exit(Runner\Job::CODE_TESTCASE);

		} else {
			return false;
		}
	}


	protected function createInstance(string $class): TestCase
	{
		// TOO: can be altered via setFactory(callable)
		return new $class;
	}
}
