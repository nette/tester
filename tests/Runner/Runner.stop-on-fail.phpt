<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Runner\Runner;
use Tester\Runner\Test;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';


class Logger implements Tester\Runner\OutputHandler
{
	public $results = [];
	public $ended = false;


	public function prepare(Test $test): void
	{
	}


	public function finish(Test $test): void
	{
		$this->results[] = [$test->getResult(), basename($test->getFile())];
	}


	public function begin(): void
	{
	}


	public function end(): void
	{
		$this->ended = true;
	}
}

$interpreter = createInterpreter();
@mkdir(__DIR__ . '/output'); // @ - directory may already exist
$marker = __DIR__ . '/output/stop-on-fail.marker';


test('Normal stop on the end', function () use ($interpreter) {
	$runner = new Runner($interpreter);
	$runner->outputHandlers[] = $logger = new Logger;
	$runner->paths = [
		__DIR__ . '/stop-on-fail/init-fail.phptx',
		__DIR__ . '/stop-on-fail/runtime-fail.phptx',
		__DIR__ . '/stop-on-fail/pass.phptx',
	];

	Assert::false($runner->run());
	Assert::same([
		[Test::Failed, 'init-fail.phptx'],
		[Test::Failed, 'runtime-fail.phptx'],
		[Test::Passed, 'pass.phptx'],
	], $logger->results);
});


test('Stop in initial phase', function () use ($interpreter) {
	$runner = new Runner($interpreter);
	$runner->outputHandlers[] = $logger = new Logger;
	$runner->stopOnFail = true;
	$runner->paths = [
		__DIR__ . '/stop-on-fail/init-fail.phptx',
		__DIR__ . '/stop-on-fail/pass.phptx',
	];

	Assert::false($runner->run());
	Assert::same([
		[Test::Failed, 'init-fail.phptx'],
	], $logger->results);
});


test('Stop in run-time', function () use ($interpreter) {
	$runner = new Runner($interpreter);
	$runner->outputHandlers[] = $logger = new Logger;
	$runner->stopOnFail = true;
	$runner->paths = [
		__DIR__ . '/stop-on-fail/runtime-fail.phptx',
		__DIR__ . '/stop-on-fail/pass.phptx',
	];

	Assert::false($runner->run());
	Assert::same([
		[Test::Failed, 'runtime-fail.phptx'],
	], $logger->results);
});


test('Stop in run-time lets running parallel jobs finish', function () use ($interpreter, $marker) {
	@unlink($marker); // @ - file may not exist
	$runner = new Runner($interpreter);
	$runner->outputHandlers[] = $logger = new Logger;
	$runner->stopOnFail = true;
	$runner->threadCount = 2;
	$runner->setEnvironmentVariable('TESTER_STOP_ON_FAIL_MARKER', $marker);
	$runner->paths = [
		__DIR__ . '/stop-on-fail/slow-pass.phptx',
		__DIR__ . '/stop-on-fail/runtime-fail.phptx',
		__DIR__ . '/stop-on-fail/pass.phptx',
	];

	Assert::false($runner->run());
	Assert::true(is_file($marker));
	sort($logger->results);
	Assert::same([
		[Test::Failed, 'runtime-fail.phptx'],
		[Test::Passed, 'slow-pass.phptx'],
	], $logger->results);
});


test('Exception in output handler terminates running jobs and ends all handlers', function () use ($interpreter, $marker) {
	@unlink($marker); // @ - file may not exist
	$runner = new Runner($interpreter);
	$runner->outputHandlers[] = $throwing = new class extends Logger {
		public function finish(Test $test): void
		{
			throw new RuntimeException('Handler failed');
		}
	};
	$runner->outputHandlers[] = $logger = new Logger;
	$runner->threadCount = 2;
	$runner->setEnvironmentVariable('TESTER_STOP_ON_FAIL_MARKER', $marker);
	$runner->paths = [
		__DIR__ . '/stop-on-fail/runtime-fail.phptx', // first, as on Windows < PHP 8.5 reading the output of a running job blocks
		__DIR__ . '/stop-on-fail/slow-pass.phptx',
	];

	Assert::exception(fn() => $runner->run(), RuntimeException::class, 'Handler failed');
	Assert::true($throwing->ended);
	Assert::true($logger->ended);

	usleep(600_000);
	Assert::false(is_file($marker));
});
