<?php declare(strict_types=1);

/**
 * Test: @phpVersion is evaluated against the interpreter version, boundaries included.
 */


use Tester\Assert;
use Tester\Runner\Test;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/OutputHandler.php';
require __DIR__ . '/../../src/Runner/Test.php';
require __DIR__ . '/../../src/Runner/TestHandler.php';
require __DIR__ . '/../../src/Runner/Runner.php';


class VersionLogger implements Tester\Runner\OutputHandler
{
	public array $results = [];


	public function prepare(Test $test): void
	{
	}


	public function finish(Test $test): void
	{
		$this->results[basename($test->getFile())] = $test->getResult();
	}


	public function begin(): void
	{
	}


	public function end(): void
	{
	}
}


$interpreter = createInterpreter();
$version = $interpreter->getVersion();
[$major, $minor] = explode('.', $version);

@mkdir(__DIR__ . '/output'); // @ - directory may already exist
$dir = __DIR__ . '/output/phpVersion';
Tester\Helpers::purge($dir);

$cases = [
	'exact' => $version,                       // >= own version must run
	'exactEq' => "== $version",
	'exactLe' => "<= $version",
	'exactLt' => "< $version",                 // own version is not lower than itself
	'exactGt' => "> $version",
	'exactNe' => "!= $version",
	'twoPart' => "$major.$minor",
	'future' => ($major + 1) . '.0',
	'past' => '5.6',
];
foreach ($cases as $name => $annotation) {
	file_put_contents("$dir/$name.phptx", "<?php\n\n/**\n * @phpVersion $annotation\n */\n");
}

$runner = new Tester\Runner\Runner($interpreter);
$runner->paths[] = $dir . '/*.phptx';
$runner->outputHandlers[] = $logger = new VersionLogger;
$runner->run();

Assert::same([
	'exact.phptx' => Test::Passed,
	'exactEq.phptx' => Test::Passed,
	'exactGt.phptx' => Test::Skipped,
	'exactLe.phptx' => Test::Passed,
	'exactLt.phptx' => Test::Skipped,
	'exactNe.phptx' => Test::Skipped,
	'future.phptx' => Test::Skipped,
	'past.phptx' => Test::Passed,
	'twoPart.phptx' => Test::Passed,
], (function (array $r) {
	ksort($r);
	return $r;
})($logger->results));

Tester\Helpers::purge($dir);
rmdir($dir);
