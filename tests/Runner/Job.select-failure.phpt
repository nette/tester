<?php declare(strict_types=1);

namespace Tester\Runner;

use Tester\{Assert, Environment};

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/Test.php';


class SelectMock
{
	public static \Closure $fail;
	public static int $calls = 0;
}


/** Replaces the native function in class Job. */
function stream_select(?array &$read, ?array &$write, ?array &$except, ?int $seconds, ?int $microseconds = null): int|false // dresscode:ignore naming.function -- shadows the native function
{
	return (SelectMock::$fail)(SelectMock::$calls++)
		? false
		: \stream_select($read, $write, $except, $seconds, $microseconds);
}


if (PHP_OS_FAMILY !== 'Windows' || PHP_VERSION_ID < 80500) {
	Environment::skip('Requires Windows and PHP 8.5.');
}


foreach (['always' => [fn() => true, 30], 'sometimes' => [fn($i) => $i % 2 === 0, 5000]] as $name => [$fail, $lines]) {
	test("stream_select() fails $name", function () use ($fail, $lines) {
		SelectMock::$fail = $fail;
		$test = (new Test(__DIR__ . '/select/emit.phptx'))->withArguments(['lines' => (string) $lines, 'sleep' => '500']);
		$job = new Job($test, createInterpreter());
		$job->run(async: true);
		for ($count = 0; $job->isRunning(); $count++) {
			Job::waitForActivity([$job]);
		}
		Assert::true(SelectMock::$calls > 0);
		Assert::true($count < 5000, "$count checks"); // waits although the function fails
		Assert::same(0, $job->getExitCode());
		Assert::same(implode('', array_map(fn($i) => sprintf("%099d\n", $i), range(0, $lines - 1))), $job->getTest()->stdout);
	});
}
