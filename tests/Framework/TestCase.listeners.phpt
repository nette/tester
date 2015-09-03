<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class TestCaseTest extends Tester\TestCase
{
	public $calls = array();

	public function testPass()
	{
		$this->calls[] = __METHOD__;
		Assert::true(TRUE);
	}

	/**
	 * @dataProvider dataProvider
	 */
	public function testPassDataProvider($arg1, $arg2)
	{
		$this->calls[] = __METHOD__ . " $arg1 $arg2";
		Assert::true(TRUE);
	}

	public function dataProvider()
	{
		return array(
			array('a', '1'),
			array('b', '2')
		);
	}

	public function testFail()
	{
		$this->calls[] = __METHOD__;
		Assert::true(FALSE);
	}

	public function setUp()
	{
		$this->calls[] = __METHOD__;
	}

	public function tearDown()
	{
		$this->calls[] = __METHOD__;
	}

}

class TestListener implements
	Tester\ITestResultListener,
	Tester\IRunTestListener,
	Tester\ISetUpListener,
	Tester\ITearDownListener {

	private $expectedTestCase;

	private function call($method, $args) {
		Tester\Dumper::toLine($args);
		$testCase = $args[0];
		$testName = $args[1];
		$testArgs = isset($args[2]) ? $args[2] : array();
		$ex = isset($args[3]) ? $args[3] : null;
		Assert::same($this->expectedTestCase, $testCase);
		$testCase->calls[] = get_class($this) . " $method $testName(" .
			implode(', ', $testArgs) . ')' . ($ex ? ' ' . $ex->getMessage() : $ex);
	}

	public function __construct($expectedTestCase)
	{
		$this->expectedTestCase = $expectedTestCase;
	}

	public function onBeforeRunTest(Tester\TestCase $testCase, $testName)
	{
		$this->call(__METHOD__, func_get_args());
	}

	public function onAfterRunTest(Tester\TestCase $testCase, $testName)
	{
		$this->call(__METHOD__, func_get_args());
	}

	public function onBeforeSetUp(Tester\TestCase $testCase, $testName, $args)
	{
		$this->call(__METHOD__, func_get_args());
	}

	public function onAfterSetUp(Tester\TestCase $testCase, $testName, $args)
	{
		$this->call(__METHOD__, func_get_args());
	}

	public function onBeforeTearDown(Tester\TestCase $testCase, $testName, $args)
	{
		$this->call(__METHOD__, func_get_args());
	}

	public function onAfterTearDown(Tester\TestCase $testCase, $testName, $args)
	{
		$this->call(__METHOD__, func_get_args());
	}

	public function onTestPass(Tester\TestCase $testCase, $testName, $args)
	{
		$this->call(__METHOD__, func_get_args());
	}

	public function onTestFail(Tester\TestCase $testCase, $testName, $args, $ex)
	{
		$this->call(__METHOD__, func_get_args());
	}

}

$test = new TestCaseTest;
$test->addListener(new TestListener($test));

$test->calls = array();
$test->run('testPass');
Assert::same(array(
	'TestListener TestListener::onBeforeRunTest testPass()',
	'TestListener TestListener::onBeforeSetUp testPass()',
	'TestCaseTest::setUp',
	'TestListener TestListener::onAfterSetUp testPass()',
	'TestCaseTest::testPass',
	'TestListener TestListener::onBeforeTearDown testPass()',
	'TestCaseTest::tearDown',
	'TestListener TestListener::onAfterTearDown testPass()',
	'TestListener TestListener::onTestPass testPass()',
	'TestListener TestListener::onAfterRunTest testPass()'
), $test->calls);

$test->calls = array();
$test->run('testPassDataProvider');
Assert::same(array(
	'TestListener TestListener::onBeforeRunTest testPassDataProvider()',
	'TestListener TestListener::onBeforeSetUp testPassDataProvider(a, 1)',
	'TestCaseTest::setUp',
	'TestListener TestListener::onAfterSetUp testPassDataProvider(a, 1)',
	'TestCaseTest::testPassDataProvider a 1',
	'TestListener TestListener::onBeforeTearDown testPassDataProvider(a, 1)',
	'TestCaseTest::tearDown',
	'TestListener TestListener::onAfterTearDown testPassDataProvider(a, 1)',
	'TestListener TestListener::onTestPass testPassDataProvider(a, 1)',
	'TestListener TestListener::onAfterRunTest testPassDataProvider()',
	'TestListener TestListener::onBeforeSetUp testPassDataProvider(b, 2)',
	'TestCaseTest::setUp',
	'TestListener TestListener::onAfterSetUp testPassDataProvider(b, 2)',
	'TestCaseTest::testPassDataProvider b 2',
	'TestListener TestListener::onBeforeTearDown testPassDataProvider(b, 2)',
	'TestCaseTest::tearDown',
	'TestListener TestListener::onAfterTearDown testPassDataProvider(b, 2)',
	'TestListener TestListener::onTestPass testPassDataProvider(b, 2)',
	'TestListener TestListener::onAfterRunTest testPassDataProvider()',
), $test->calls);

$test->calls = array();
Assert::exception(function () use($test) {
	$test->run('testFail');
}, 'Tester\AssertException', 'FALSE should be TRUE in testFail()');
Assert::same(array(
	'TestListener TestListener::onBeforeRunTest testFail()',
	'TestListener TestListener::onBeforeSetUp testFail()',
	'TestCaseTest::setUp',
	'TestListener TestListener::onAfterSetUp testFail()',
	'TestCaseTest::testFail',
	'TestListener TestListener::onBeforeTearDown testFail()',
	'TestCaseTest::tearDown',
	'TestListener TestListener::onAfterTearDown testFail()',
	'TestListener TestListener::onTestFail testFail() FALSE should be TRUE in testFail()',
	'TestListener TestListener::onAfterRunTest testFail()'
), $test->calls);

$test->calls = array();
Assert::exception(function () use($test) {
	$test->run();
}, 'Tester\AssertException', 'FALSE should be TRUE in testFail()');
Assert::same(array(
	'TestListener TestListener::onBeforeRunTest testPass()',
	'TestListener TestListener::onBeforeSetUp testPass()',
	'TestCaseTest::setUp',
	'TestListener TestListener::onAfterSetUp testPass()',
	'TestCaseTest::testPass',
	'TestListener TestListener::onBeforeTearDown testPass()',
	'TestCaseTest::tearDown',
	'TestListener TestListener::onAfterTearDown testPass()',
	'TestListener TestListener::onTestPass testPass()',
	'TestListener TestListener::onAfterRunTest testPass()',
	'TestListener TestListener::onBeforeRunTest testPassDataProvider()',
	'TestListener TestListener::onBeforeSetUp testPassDataProvider(a, 1)',
	'TestCaseTest::setUp',
	'TestListener TestListener::onAfterSetUp testPassDataProvider(a, 1)',
	'TestCaseTest::testPassDataProvider a 1',
	'TestListener TestListener::onBeforeTearDown testPassDataProvider(a, 1)',
	'TestCaseTest::tearDown',
	'TestListener TestListener::onAfterTearDown testPassDataProvider(a, 1)',
	'TestListener TestListener::onTestPass testPassDataProvider(a, 1)',
	'TestListener TestListener::onAfterRunTest testPassDataProvider()',
	'TestListener TestListener::onBeforeSetUp testPassDataProvider(b, 2)',
	'TestCaseTest::setUp',
	'TestListener TestListener::onAfterSetUp testPassDataProvider(b, 2)',
	'TestCaseTest::testPassDataProvider b 2',
	'TestListener TestListener::onBeforeTearDown testPassDataProvider(b, 2)',
	'TestCaseTest::tearDown',
	'TestListener TestListener::onAfterTearDown testPassDataProvider(b, 2)',
	'TestListener TestListener::onTestPass testPassDataProvider(b, 2)',
	'TestListener TestListener::onAfterRunTest testPassDataProvider()',
	'TestListener TestListener::onBeforeRunTest testFail()',
	'TestListener TestListener::onBeforeSetUp testFail()',
	'TestCaseTest::setUp',
	'TestListener TestListener::onAfterSetUp testFail()',
	'TestCaseTest::testFail',
	'TestListener TestListener::onBeforeTearDown testFail()',
	'TestCaseTest::tearDown',
	'TestListener TestListener::onAfterTearDown testFail()',
	'TestListener TestListener::onTestFail testFail() FALSE should be TRUE in testFail()',
	'TestListener TestListener::onAfterRunTest testFail()',
), $test->calls);

class TestListener2 extends TestListener {}
$test->addListener(new TestListener2($test));

$test->calls = array();
Assert::exception(function () use($test) {
	$test->run();
}, 'Tester\AssertException', 'FALSE should be TRUE in testFail()');
Assert::same(array(
	'TestListener TestListener::onBeforeRunTest testPass()',
	'TestListener2 TestListener::onBeforeRunTest testPass()',
	'TestListener TestListener::onBeforeSetUp testPass()',
	'TestListener2 TestListener::onBeforeSetUp testPass()',
	'TestCaseTest::setUp',
	'TestListener TestListener::onAfterSetUp testPass()',
	'TestListener2 TestListener::onAfterSetUp testPass()',
	'TestCaseTest::testPass',
	'TestListener TestListener::onBeforeTearDown testPass()',
	'TestListener2 TestListener::onBeforeTearDown testPass()',
	'TestCaseTest::tearDown',
	'TestListener TestListener::onAfterTearDown testPass()',
	'TestListener2 TestListener::onAfterTearDown testPass()',
	'TestListener TestListener::onTestPass testPass()',
	'TestListener2 TestListener::onTestPass testPass()',
	'TestListener TestListener::onAfterRunTest testPass()',
	'TestListener2 TestListener::onAfterRunTest testPass()',
	'TestListener TestListener::onBeforeRunTest testPassDataProvider()',
	'TestListener2 TestListener::onBeforeRunTest testPassDataProvider()',
	'TestListener TestListener::onBeforeSetUp testPassDataProvider(a, 1)',
	'TestListener2 TestListener::onBeforeSetUp testPassDataProvider(a, 1)',
	'TestCaseTest::setUp',
	'TestListener TestListener::onAfterSetUp testPassDataProvider(a, 1)',
	'TestListener2 TestListener::onAfterSetUp testPassDataProvider(a, 1)',
	'TestCaseTest::testPassDataProvider a 1',
	'TestListener TestListener::onBeforeTearDown testPassDataProvider(a, 1)',
	'TestListener2 TestListener::onBeforeTearDown testPassDataProvider(a, 1)',
	'TestCaseTest::tearDown',
	'TestListener TestListener::onAfterTearDown testPassDataProvider(a, 1)',
	'TestListener2 TestListener::onAfterTearDown testPassDataProvider(a, 1)',
	'TestListener TestListener::onTestPass testPassDataProvider(a, 1)',
	'TestListener2 TestListener::onTestPass testPassDataProvider(a, 1)',
	'TestListener TestListener::onAfterRunTest testPassDataProvider()',
	'TestListener2 TestListener::onAfterRunTest testPassDataProvider()',
	'TestListener TestListener::onBeforeSetUp testPassDataProvider(b, 2)',
	'TestListener2 TestListener::onBeforeSetUp testPassDataProvider(b, 2)',
	'TestCaseTest::setUp',
	'TestListener TestListener::onAfterSetUp testPassDataProvider(b, 2)',
	'TestListener2 TestListener::onAfterSetUp testPassDataProvider(b, 2)',
	'TestCaseTest::testPassDataProvider b 2',
	'TestListener TestListener::onBeforeTearDown testPassDataProvider(b, 2)',
	'TestListener2 TestListener::onBeforeTearDown testPassDataProvider(b, 2)',
	'TestCaseTest::tearDown',
	'TestListener TestListener::onAfterTearDown testPassDataProvider(b, 2)',
	'TestListener2 TestListener::onAfterTearDown testPassDataProvider(b, 2)',
	'TestListener TestListener::onTestPass testPassDataProvider(b, 2)',
	'TestListener2 TestListener::onTestPass testPassDataProvider(b, 2)',
	'TestListener TestListener::onAfterRunTest testPassDataProvider()',
	'TestListener2 TestListener::onAfterRunTest testPassDataProvider()',
	'TestListener TestListener::onBeforeRunTest testFail()',
	'TestListener2 TestListener::onBeforeRunTest testFail()',
	'TestListener TestListener::onBeforeSetUp testFail()',
	'TestListener2 TestListener::onBeforeSetUp testFail()',
	'TestCaseTest::setUp',
	'TestListener TestListener::onAfterSetUp testFail()',
	'TestListener2 TestListener::onAfterSetUp testFail()',
	'TestCaseTest::testFail',
	'TestListener TestListener::onBeforeTearDown testFail()',
	'TestListener2 TestListener::onBeforeTearDown testFail()',
	'TestCaseTest::tearDown',
	'TestListener TestListener::onAfterTearDown testFail()',
	'TestListener2 TestListener::onAfterTearDown testFail()',
	'TestListener TestListener::onTestFail testFail() FALSE should be TRUE in testFail()',
	'TestListener2 TestListener::onTestFail testFail() FALSE should be TRUE in testFail()',
	'TestListener TestListener::onAfterRunTest testFail()',
	'TestListener2 TestListener::onAfterRunTest testFail()',
), $test->calls);
