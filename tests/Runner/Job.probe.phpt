<?php declare(strict_types=1);

// The result and the speed of the probe are simulated by replacing it next to a copy of class Job.

use Tester\{Assert, Helpers};
use Tester\Runner\{Job, Test};

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/Runner/Test.php';


/** Runs jobs in a separate process, which uses a copy of Job with the given probe. */
function runWithProbe(string $probe, float $probeTimeout = 5): array
{
	$dir = Helpers::prepareTempDir(sys_get_temp_dir()) . '/probe-' . getmypid() . '-' . md5($probe);
	@mkdir($dir); // @ directory may already exist
	$code = file_get_contents(__DIR__ . '/../../src/Runner/Job.php');
	file_put_contents("$dir/Job.php", preg_replace('#const ProbeTimeout = .+?;#', "const ProbeTimeout = $probeTimeout;", $code));
	file_put_contents("$dir/pipe-probe.php", '<?php ' . $probe);
	$job = new Job((new Test(__DIR__ . '/pipes/probe.phptx'))->withArguments(["$dir/Job.php"]), createInterpreter());
	$job->run();
	Helpers::purge($dir);
	rmdir($dir);
	return json_decode($job->getTest()->stdout, true) ?? throw new Exception($job->getTest()->stdout . $job->getTest()->stderr);
}


test('a slow probe neither prolongs the first test nor uses up its time limit', function () {
	$res = runWithProbe('sleep(2); exit(0);');
	Assert::same([false, 0, 'done'], [$res['timedOut'], $res['exitCode'], $res['stdout']]); // the limit is 1.5 s
	Assert::true($res['duration'] < 1.5, "duration $res[duration]");
	if (PHP_OS_FAMILY === 'Windows' && PHP_VERSION_ID < 80500) {
		Assert::true($res['total'] > 2); // the probe has really run
		Assert::true($res['canPoll']);
	}
});


test('a failing probe', function () {
	$res = runWithProbe('exit(1);');
	Assert::same([false, 0, 'done'], [$res['timedOut'], $res['exitCode'], $res['stdout']]);
	Assert::false($res['canPoll'] ?? false);
	Assert::same([true, 300_001], [$res['sameOutput'], $res['length']]);
});


test('a hanging probe is terminated', function () {
	$res = runWithProbe('sleep(60);', 1);
	Assert::same([false, 0, 'done'], [$res['timedOut'], $res['exitCode'], $res['stdout']]);
	Assert::true($res['duration'] < 1.5, "duration $res[duration]");
	Assert::true($res['total'] < 20, "total $res[total]");
	Assert::false($res['canPoll'] ?? false);
	Assert::same([true, 300_001], [$res['sameOutput'], $res['length']]);
});
