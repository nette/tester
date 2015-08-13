<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class TestCaseTest extends Tester\TestCase
{
	public $calls = array();

	public function testPass()
	{
		$this->calls[] = 'testPass';
		Assert::true(TRUE);
	}

	public function testFail()
	{
		$this->calls[] = 'testFail';
		Assert::true(FALSE);
	}

	public function setUp() {
		$this->calls[] = 'setUp';
	}

	public function tearDown() {
		$this->calls[] = 'tearDown';
	}

}

$test = new TestCaseTest;

$test->onBeforeRun[] = function($testCase) use ($test) {
	$testCase->calls[] = 'onBeforeRun';
	Assert::same($test, $testCase);
};
$test->onAfterRun[] = function($testCase) use ($test) {
	$testCase->calls[] = 'onAfterRun';
	Assert::same($test, $testCase);
};
$test->onBeforeRunTest[] = function($testCase, $testName) use ($test) {
	$testCase->calls[] = "onBeforeRunTest $testName";
	Assert::same($test, $testCase);
};
$test->onAfterRunTest[] = function($testCase, $testName) use ($test) {
	$testCase->calls[] = "onAfterRunTest $testName";
	Assert::same($test, $testCase);
};
$test->onBeforeSetUp[] = function($testCase, $testName) use ($test) {
	$testCase->calls[] = "onBeforeSetUp $testName";
	Assert::same($test, $testCase);
};
$test->onAfterSetUp[] = function($testCase, $testName) use ($test) {
	$testCase->calls[] = "onAfterSetUp $testName";
	Assert::same($test, $testCase);
};
$test->onBeforeTearDown[] = function($testCase, $testName) use ($test) {
	$testCase->calls[] = "onBeforeTearDown $testName";
	Assert::same($test, $testCase);
};
$test->onAfterTearDown[] = function($testCase, $testName) use ($test) {
	$testCase->calls[] = "onAfterTearDown $testName";
	Assert::same($test, $testCase);
};

$test->calls = array();
$test->run('testPass');
Assert::same(array(
	'onBeforeRun',
	'onBeforeRunTest testPass',
	'onBeforeSetUp testPass',
	'setUp',
	'onAfterSetUp testPass',
	'testPass',
	'onBeforeTearDown testPass',
	'tearDown',
	'onAfterTearDown testPass',
	'onAfterRunTest testPass',
	'onAfterRun',
), $test->calls);

$test->calls = array();
Assert::exception(function () use($test) {
	$test->run('testFail');
}, 'Tester\AssertException', 'FALSE should be TRUE in testFail()');
Assert::same(array(
	'onBeforeRun',
	'onBeforeRunTest testFail',
	'onBeforeSetUp testFail',
	'setUp',
	'onAfterSetUp testFail',
	'testFail',
	'onBeforeTearDown testFail',
	'tearDown',
	'onAfterTearDown testFail',
	'onAfterRunTest testFail',
	'onAfterRun',
), $test->calls);

$test->calls = array();
Assert::exception(function () use($test) {
	$test->run();
}, 'Tester\AssertException', 'FALSE should be TRUE in testFail()');
Assert::same(array(
	'onBeforeRun',
	'onBeforeRunTest testPass',
	'onBeforeSetUp testPass',
	'setUp',
	'onAfterSetUp testPass',
	'testPass',
	'onBeforeTearDown testPass',
	'tearDown',
	'onAfterTearDown testPass',
	'onAfterRunTest testPass',
	'onBeforeRunTest testFail',
	'onBeforeSetUp testFail',
	'setUp',
	'onAfterSetUp testFail',
	'testFail',
	'onBeforeTearDown testFail',
	'tearDown',
	'onAfterTearDown testFail',
	'onAfterRunTest testFail',
	'onAfterRun',
), $test->calls);

$test->onBeforeRun[] = function($testCase) use ($test) {
	$testCase->calls[] = 'onBeforeRun2';
	Assert::same($test, $testCase);
};
$test->onAfterRun[] = function($testCase) use ($test) {
	$testCase->calls[] = 'onAfterRun2';
	Assert::same($test, $testCase);
};
$test->onBeforeRunTest[] = function($testCase, $testName) use ($test) {
	$testCase->calls[] = "onBeforeRunTest2 $testName";
	Assert::same($test, $testCase);
};
$test->onAfterRunTest[] = function($testCase, $testName) use ($test) {
	$testCase->calls[] = "onAfterRunTest2 $testName";
	Assert::same($test, $testCase);
};
$test->onBeforeSetUp[] = function($testCase, $testName) use ($test) {
	$testCase->calls[] = "onBeforeSetUp2 $testName";
	Assert::same($test, $testCase);
};
$test->onAfterSetUp[] = function($testCase, $testName) use ($test) {
	$testCase->calls[] = "onAfterSetUp2 $testName";
	Assert::same($test, $testCase);
};
$test->onBeforeTearDown[] = function($testCase, $testName) use ($test) {
	$testCase->calls[] = "onBeforeTearDown2 $testName";
	Assert::same($test, $testCase);
};
$test->onAfterTearDown[] = function($testCase, $testName) use ($test) {
	$testCase->calls[] = "onAfterTearDown2 $testName";
	Assert::same($test, $testCase);
};

$test->calls = array();
Assert::exception(function () use($test) {
	$test->run();
}, 'Tester\AssertException', 'FALSE should be TRUE in testFail()');
Assert::same(array(
	'onBeforeRun',
	'onBeforeRun2',
	'onBeforeRunTest testPass',
	'onBeforeRunTest2 testPass',
	'onBeforeSetUp testPass',
	'onBeforeSetUp2 testPass',
	'setUp',
	'onAfterSetUp testPass',
	'onAfterSetUp2 testPass',
	'testPass',
	'onBeforeTearDown testPass',
	'onBeforeTearDown2 testPass',
	'tearDown',
	'onAfterTearDown testPass',
	'onAfterTearDown2 testPass',
	'onAfterRunTest testPass',
	'onAfterRunTest2 testPass',
	'onBeforeRunTest testFail',
	'onBeforeRunTest2 testFail',
	'onBeforeSetUp testFail',
	'onBeforeSetUp2 testFail',
	'setUp',
	'onAfterSetUp testFail',
	'onAfterSetUp2 testFail',
	'testFail',
	'onBeforeTearDown testFail',
	'onBeforeTearDown2 testFail',
	'tearDown',
	'onAfterTearDown testFail',
	'onAfterTearDown2 testFail',
	'onAfterRunTest testFail',
	'onAfterRunTest2 testFail',
	'onAfterRun',
	'onAfterRun2'
), $test->calls);
