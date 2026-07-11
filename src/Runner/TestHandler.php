<?php declare(strict_types=1);

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester;
use Tester\{Dumper, Helpers, TestCase};
use function count, is_array, is_string;


/**
 * Processes test annotations to initiate test variants and assess their results.
 */
class TestHandler
{
	private const HttpOk = 200;
	private ?string $tempDir = null;

	/**
	 * file => [[mtime, size], [annotations, title]]
	 * @var array<string, array{array{int|false, int|false}, array{array<string|string[]>, ?string}}>
	 */
	private array $annotationsCache = [];


	public function __construct(
		private Runner $runner,
	) {
	}


	public function setTempDirectory(?string $path): void
	{
		$this->tempDir = $path;
	}


	public function initiate(string $file): void
	{
		[$annotations, $title] = $this->getAnnotations($file);
		$php = $this->runner->getInterpreter();

		$tests = [new Test($file, $title)];
		foreach (get_class_methods($this) as $method) {
			if (!preg_match('#^initiate(.+)#', strtolower($method), $m) || !isset($annotations[$m[1]])) {
				continue;
			}

			foreach ((array) $annotations[$m[1]] as $value) {
				/** @var Test[] $prepared */
				$prepared = [];
				foreach ($tests as $test) {
					$res = $this->$method($test, $value, $php);
					if ($res === null) {
						$prepared[] = $test;
					} else {
						foreach (is_array($res) ? $res : [$res] as $testVariety) {
							assert($testVariety instanceof Test);
							if ($testVariety->hasResult()) {
								$this->runner->prepareTest($testVariety);
								$this->runner->finishTest($testVariety);
							} else {
								$prepared[] = $testVariety;
							}
						}
					}
				}

				$tests = $prepared;
			}
		}

		foreach ($tests as $test) {
			$this->runner->prepareTest($test);
			$job = new Job($test, $php, $this->runner->getEnvironmentVariables());
			$job->setTempDirectory($this->tempDir);
			$this->runner->addJob($job);
		}
	}


	public function assess(Job $job): void
	{
		$test = $job->getTest();
		$annotations = $this->getAnnotations($test->getFile())[0] += [
			'exitcode' => Job::CodeOk,
			'httpcode' => self::HttpOk,
		];

		foreach (get_class_methods($this) as $method) {
			if (!preg_match('#^assess(.+)#', strtolower($method), $m) || !isset($annotations[$m[1]])) {
				continue;
			}

			foreach ((array) $annotations[$m[1]] as $arg) {
				if ($res = $this->$method($job, $arg)) {
					$this->runner->finishTest($res);
					return;
				}
			}
		}

		$this->runner->finishTest($test->withResult(Test::Passed, $test->message, $job->getDuration()));
	}


	private function initiateSkip(Test $test, string $message): Test
	{
		return $test->withResult(Test::Skipped, $message);
	}


	private function initiatePhpVersion(Test $test, string $version, PhpInterpreter $interpreter): ?Test
	{
		if (
			preg_match('#^(<=|<|==|=|!=|<>|>=|>)?\s*(.+)#', $version, $matches)
			&& !version_compare($interpreter->getVersion(), $matches[2], $matches[1] ?: '>=')
		) {
			return $test->withResult(Test::Skipped, "Requires PHP $version.");
		}

		return null;
	}


	private function initiatePhpExtension(Test $test, string $value, PhpInterpreter $interpreter): ?Test
	{
		foreach (preg_split('#[\s,]+#', $value) as $extension) {
			if (!$interpreter->hasExtension($extension)) {
				return $test->withResult(Test::Skipped, "Requires PHP extension $extension.");
			}
		}

		return null;
	}


	private function initiatePhpIni(Test $test, string $pair, PhpInterpreter &$interpreter): void
	{
		$parts = explode('=', $pair, 2);
		$interpreter = $interpreter->withPhpIniOption($parts[0], $parts[1] ?? null);
	}


	/** @return list<Test>|Test */
	private function initiateDataProvider(Test $test, string $provider): array|Test
	{
		try {
			[$dataFile, $query, $optional] = Tester\DataProvider::parseAnnotation($provider, $test->getFile());
			$data = Tester\DataProvider::load($dataFile, $query);
			if (count($data) < 1) {
				throw new \Exception("No records in data provider file '$dataFile'" . ($query ? " for query '$query'" : '') . '.');
			}
		} catch (\Throwable $e) {
			return $test->withResult(empty($optional) ? Test::Failed : Test::Skipped, $e->getMessage());
		}

		return array_map(
			fn(string $item): Test => $test->withArguments(['dataprovider' => "$item|$dataFile"]),
			array_keys($data),
		);
	}


	/** @return list<Test>|Test */
	private function initiateMultiple(Test $test, string $count): array|Test
	{
		if (!preg_match('#^[1-9]\d*$#D', $count)) {
			return $test->withResult(Test::Failed, "Annotation @multiple expects a positive integer, '$count' given.");
		}

		return array_map(
			fn(int $i): Test => $test->withArguments(['multiple' => $i]),
			range(0, (int) $count - 1),
		);
	}


	/** @return list<Test> */
	private function initiateTestCase(Test $test, string $value, PhpInterpreter $interpreter): array
	{
		if (($methods = $this->loadTestCaseMethods($test)) !== null) {
			return $this->createTestCaseVariants($test, $methods);
		}

		// discovers methods in a job running in parallel with other tests
		$job = new Job($test->withArguments(['method' => TestCase::ListMethods]), $interpreter, $this->runner->getEnvironmentVariables());
		$job->setTempDirectory($this->tempDir);
		$this->runner->addJob(
			$job,
			fn(Job $job) => $this->finishTestCaseDiscovery($job, $test, $interpreter),
		);
		return [];
	}


	/**
	 * Processes the result of the methods-listing job: either finishes the test with an error,
	 * or schedules a job for each discovered method.
	 */
	private function finishTestCaseDiscovery(Job $job, Test $test, PhpInterpreter $interpreter): void
	{
		$methods = $this->processTestCaseDiscovery($job, $test);
		if ($methods instanceof Test) {
			$this->runner->prepareTest($methods);
			$this->runner->finishTest($methods);
			return;
		}

		foreach ($this->createTestCaseVariants($test, $methods) as $variant) {
			$this->runner->prepareTest($variant);
			$variantJob = new Job($variant, $interpreter, $this->runner->getEnvironmentVariables());
			$variantJob->setTempDirectory($this->tempDir);
			$this->runner->addJob($variantJob);
		}
	}


	/** @return Test|list<string>  finished test on error, or list of method names */
	private function processTestCaseDiscovery(Job $job, Test $test): Test|array
	{
		if (in_array($job->getExitCode(), [Job::CodeError, Job::CodeFail, Job::CodeSkip], true)) {
			return $test->withResult($job->getExitCode() === Job::CodeSkip ? Test::Skipped : Test::Failed, $job->getTest()->getOutput());
		}

		$stdout = $job->getTest()->stdout;

		if (!preg_match('#^TestCase:([^\n]+)$#m', $stdout, $m)) {
			return $test->withResult(
				Test::Failed,
				"Cannot list TestCase methods in file '{$test->getFile()}'. Do you call TestCase::run() in it?",
			);
		}

		$testCaseClass = $m[1];

		preg_match_all('#^Method:([^\n]+)$#m', $stdout, $m);
		if (count($m[1]) < 1) {
			return $test->withResult(Test::Skipped, "Class $testCaseClass in file '{$test->getFile()}' does not contain test methods.");
		}

		$methods = $m[1];

		if ($cacheFile = $this->getTestCaseCacheFile($test)) {
			preg_match_all('#^Dependency:([^\n]+)$#m', $stdout, $m);
			file_put_contents($cacheFile, serialize([
				'methods' => $methods,
				'files' => array_combine($m[1], array_map('filemtime', $m[1])),
			]));
		}

		return $methods;
	}


	/** @return ?list<string>  method names from a valid cache, or null */
	private function loadTestCaseMethods(Test $test): ?array
	{
		$cacheFile = $this->getTestCaseCacheFile($test);
		if (!$cacheFile || !is_file($cacheFile)) {
			return null;
		}

		$cache = unserialize(Helpers::readFile($cacheFile));
		foreach ($cache['files'] as $path => $mTime) {
			if (!is_file($path) || filemtime($path) !== $mTime) {
				return null;
			}
		}

		return $cache['methods'];
	}


	private function getTestCaseCacheFile(Test $test): ?string
	{
		return $this->tempDir
			? $this->tempDir . DIRECTORY_SEPARATOR . 'TestHandler.testCase.' . md5($test->getSignature()) . '.list'
			: null;
	}


	/**
	 * @param  list<string>  $methods
	 * @return list<Test>
	 */
	private function createTestCaseVariants(Test $test, array $methods): array
	{
		return array_map(
			fn(string $method): Test => $test
				->withTitle(trim("$test->title $method"))
				->withArguments(['method' => $method]),
			$methods,
		);
	}


	private function assessExitCode(Job $job, string|int $code): ?Test
	{
		$code = (int) $code;
		if ($job->getExitCode() === Job::CodeSkip) {
			$message = preg_match('#.*Skipped:\n(.*?)$#Ds', $output = $job->getTest()->stdout, $m)
				? $m[1]
				: $output;
			return $job->getTest()->withResult(Test::Skipped, trim($message));

		} elseif ($job->getExitCode() !== $code) {
			$message = $job->getExitCode() !== Job::CodeFail
				? "Exited with error code {$job->getExitCode()} (expected $code)"
				: '';
			return $job->getTest()->withResult(Test::Failed, trim($message . "\n" . $job->getTest()->getOutput()));
		}

		return null;
	}


	private function assessHttpCode(Job $job, string|int $code): ?Test
	{
		if (!$this->runner->getInterpreter()->isCgi()) {
			return null;
		}

		$headers = $job->getHeaders();
		$actual = (int) ($headers['Status'] ?? self::HttpOk);
		$code = (int) $code;
		return $code && $code !== $actual
			? $job->getTest()->withResult(Test::Failed, "Exited with HTTP code $actual (expected $code)")
			: null;
	}


	private function assessOutputMatchFile(Job $job, string $file): ?Test
	{
		$file = dirname($job->getTest()->getFile()) . DIRECTORY_SEPARATOR . $file;
		if (!is_file($file)) {
			return $job->getTest()->withResult(Test::Failed, "Missing matching file '$file'.");
		}

		return $this->assessOutputMatch($job, Helpers::readFile($file));
	}


	private function assessOutputMatch(Job $job, string $content): ?Test
	{
		$actual = $job->getTest()->stdout;
		if (!Tester\Assert::isMatching($content, $actual)) {
			[$content, $actual] = Tester\Assert::expandMatchingPatterns($content, $actual);
			Dumper::saveOutput($job->getTest()->getFile(), $actual, '.actual');
			Dumper::saveOutput($job->getTest()->getFile(), $content, '.expected');
			return $job->getTest()->withResult(Test::Failed, 'Failed: output should match ' . Dumper::toLine($content));
		}

		return null;
	}


	/** @return array{array<string|string[]>, ?string}  [annotations, test title] */
	private function getAnnotations(string $file): array
	{
		$stamp = [@filemtime($file), @filesize($file)]; // @ - file may not exist
		if (!isset($this->annotationsCache[$file]) || $this->annotationsCache[$file][0] !== $stamp) {
			$annotations = Helpers::parseDocComment(Helpers::readFile($file));
			$testTitle = is_string($annotations[0] ?? null)
				? preg_replace('#^TEST:\s*#i', '', $annotations[0])
				: null;
			$this->annotationsCache[$file] = [$stamp, [$annotations, $testTitle]];
		}

		return $this->annotationsCache[$file][1];
	}
}
