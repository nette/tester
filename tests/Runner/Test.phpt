<?php

/**
 * TEST: Runner\Test basics.
 */

declare(strict_types=1);

use Tester\Assert;
use Tester\Runner\Test;

require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../bootstrap.php';


test(function () {
	$test = new Test('some/Test.phpt');

	Assert::null($test->title);
	Assert::null($test->message);
	Assert::same('', $test->stdout);
	Assert::same('', $test->stderr);
	Assert::same('', $test->getOutput());
	Assert::same('some/Test.phpt', $test->getFile());
	Assert::same([], $test->getArguments());
	Assert::same('some/Test.phpt', $test->getSignature());
	Assert::false($test->hasResult());
	Assert::same(Test::Prepared, $test->getResult());
	Assert::null($test->getDuration());
});


test(function () {
	$test = new Test(__FILE__, 'My test');

	Assert::same('My test', $test->title);
});


test(function () {
	$test = (new Test(__FILE__, 'My test'))->withResult(Test::Passed, 'It is done');

	Assert::true($test->hasResult());
	Assert::same(Test::Passed, $test->getResult());
	Assert::same('It is done', $test->message);

	Assert::exception(function () use ($test) {
		$test->withResult(Test::Failed, 'Foo');
	}, LogicException::class, 'Result of test is already set to ' . Test::Passed . " with message 'It is done'.");
});


test(function () {
	$test = new Test(__FILE__, 'My test');

	$test = $test->withArguments(['one', 'two' => 1]);
	Assert::same('My test', $test->title);
	Assert::match('%a%%ds%Test.phpt one two=1', $test->getSignature());

	$test = $test->withArguments(['one', 'two' => [1, 2], 'three']);
	Assert::same([
		'one',
		['two', '1'],
		'one',
		['two', '1'],
		['two', '2'],
		'three',
	], $test->getArguments());

	Assert::exception(function () use ($test) {
		$test->withResult(Test::Passed, '')->withArguments([]);
	}, LogicException::class, 'Cannot change arguments of test which already has a result.');
});
