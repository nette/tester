<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class TestCaseTest extends Tester\TestCase
{
	public function testSkip()
	{
		$this->skip('foo');
		thisIsNotExecuted();
	}
}


Assert::exception(function () {
	$test = new TestCaseTest;
	$test->runTest('testSkip');
}, Tester\TestCaseSkippedException::class, 'foo');

Assert::noError(function () {
	$test = new TestCaseTest;
	$test->run();
});
