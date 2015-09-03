<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester;


interface ITestCaseListener
{
}

interface ITestResultListener extends ITestCaseListener {

	public function onTestPass(TestCase $testCase, $testName, $args);

	public function onTestFail(TestCase $testCase, $testName, $args, $exception);

}

interface ISetUpListener extends ITestCaseListener {

	public function onBeforeSetUp(TestCase $testCase, $testName, $args);

	public function onAfterSetUp(TestCase $testCase, $testName, $args);

}

interface ITearDownListener extends ITestCaseListener {

	public function onBeforeTearDown(TestCase $testCase, $testName, $args);

	public function onAfterTearDown(TestCase $testCase, $testName, $args);

}

interface IRunTestListener extends ITestCaseListener {

	public function onBeforeRunTest(TestCase $testCase, $testName);

	public function onAfterRunTest(TestCase $testCase, $testName);

}
