<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$setUpCalled = 0;
$tearDownCalled = 0;

setUp(function () use (&$setUpCalled) {
	$setUpCalled++;
});

tearDown(function () use (&$tearDownCalled) {
	$tearDownCalled++;
});


// Test that setUp and tearDown are called after test()
test('test with setUp/tearDown', function () {
	Assert::true(true);
});
Assert::same(1, $setUpCalled);
Assert::same(1, $tearDownCalled);


// Test that both setUp and tearDown are called even when test fails
Assert::exception(
	function () use (&$setUpCalled, &$tearDownCalled) {
		test('', function () {
			throw new RuntimeException('Test failed');
		});
	},
	RuntimeException::class,
	'Test failed',
);
Assert::same(2, $setUpCalled); // setUp should have been called
Assert::same(2, $tearDownCalled); // tearDown should have been called despite the failure


// Test that setUp and tearDown are called after testException()
testException('testException with setUp/tearDown', function () {
	throw new Exception('Expected');
}, Exception::class);
Assert::same(3, $setUpCalled);
Assert::same(3, $tearDownCalled);
