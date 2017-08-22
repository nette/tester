<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester\CodeCoverage;
use Tester\Dumper;
use Tester\Environment;


/**
 * CLI Tester.
 */
class CliTester
{
	/** @var array */
	private $options;

	/** @var PhpInterpreter */
	private $interpreter;


	/** @return int|null */
	public function run()
	{
		Environment::setupColors();
		Environment::setupErrors();

		ob_start();
		$cmd = $this->loadOptions();

		Environment::$debugMode = (bool) $this->options['--debug'];
		if (isset($this->options['--colors'])) {
			Environment::$useColors = (bool) $this->options['--colors'];
		} elseif (in_array($this->options['-o'], ['tap', 'junit'], true)) {
			Environment::$useColors = false;
		}

		if ($cmd->isEmpty() || $this->options['--help']) {
			$cmd->help();
			return;
		}

		$this->createPhpInterpreter();

		if ($this->options['--info']) {
			$job = new Job(new Test(__DIR__ . '/info.php'), $this->interpreter);
			$job->run();
			echo $job->getTest()->stdout;
			return;
		}

		if ($this->options['--coverage']) {
			$coverageFile = $this->prepareCodeCoverage();
		}

		$runner = $this->createRunner();
		$runner->setEnvironmentVariable(Environment::RUNNER, 1);
		$runner->setEnvironmentVariable(Environment::COLORS, (int) Environment::$useColors);
		if (isset($coverageFile)) {
			$runner->setEnvironmentVariable(Environment::COVERAGE, $coverageFile);
		}

		if ($this->options['-o'] !== null) {
			ob_clean();
		}
		ob_end_flush();

		if ($this->options['--watch']) {
			$this->watch($runner);
			return;
		}

		$result = $runner->run();

		if (isset($coverageFile) && preg_match('#\.(?:html?|xml)\z#', $coverageFile)) {
			$this->finishCodeCoverage($coverageFile);
		}

		return $result ? 0 : 1;
	}


	/** @return CommandLine */
	private function loadOptions()
	{
		echo <<<'XX'
 _____ ___  ___ _____ ___  ___
|_   _/ __)( __/_   _/ __)| _ )
  |_| \___ /___) |_| \___ |_|_\  v2.0.0


XX;

		$cmd = new CommandLine(<<<'XX'
Usage:
    tester.php [options] [<test file> | <directory>]...

Options:
    -p <path>                    Specify PHP interpreter to run (default: php).
    -c <path>                    Look for php.ini file (or look in directory) <path>.
    -C                           Use system-wide php.ini.
    -l | --log <path>            Write log to file <path>.
    -d <key=value>...            Define INI entry 'key' with value 'val'.
    -s                           Show information about skipped tests.
    --stop-on-fail               Stop execution upon the first failure.
    -j <num>                     Run <num> jobs in parallel (default: 8).
    -o <console|tap|junit|none>  Specify output format.
    -w | --watch <path>          Watch directory.
    -i | --info                  Show tests environment info and exit.
    --setup <path>               Script for runner setup.
    --temp <path>                Path to temporary directory. Default by sys_get_temp_dir().
    --colors [1|0]               Enable or disable colors.
    --coverage <path>            Generate code coverage report to file.
    --coverage-src <path>        Path to source code.
    -h | --help                  This help.

XX
		, [
			'-c' => [CommandLine::REALPATH => true],
			'--watch' => [CommandLine::REPEATABLE => true, CommandLine::REALPATH => true],
			'--setup' => [CommandLine::REALPATH => true],
			'--temp' => [CommandLine::REALPATH => true],
			'paths' => [CommandLine::REPEATABLE => true, CommandLine::VALUE => getcwd()],
			'--debug' => [],
			'--coverage-src' => [CommandLine::REALPATH => true],
		]);

		if (isset($_SERVER['argv'])) {
			if ($tmp = array_search('-log', $_SERVER['argv'], true)) {
				$_SERVER['argv'][$tmp] = '--log';
			}

			if ($tmp = array_search('--tap', $_SERVER['argv'], true)) {
				unset($_SERVER['argv'][$tmp]);
				$_SERVER['argv'] = array_merge($_SERVER['argv'], ['-o', 'tap']);
			}

			if (array_search('-p', $_SERVER['argv'], true) === false) {
				echo "Note: Default interpreter is CLI since Tester v2.0. It used to be CGI.\n";
			}
		}

		$this->options = $cmd->parse();
		if ($this->options['--temp'] === null) {
			if (($temp = sys_get_temp_dir()) === '') {
				echo "Note: System temporary directory is not set.\n";
			} elseif (($real = realpath($temp)) === false) {
				echo "Note: System temporary directory '$temp' does not exist.\n";
			} else {
				$this->options['--temp'] = rtrim($real, DIRECTORY_SEPARATOR);
			}
		}

		return $cmd;
	}


	/** @return void */
	private function createPhpInterpreter()
	{
		$args = $this->options['-C'] ? [] : ['-n'];
		if ($this->options['-c']) {
			array_push($args, '-c', $this->options['-c']);
		} elseif (!$this->options['--info'] && !$this->options['-C']) {
			echo "Note: No php.ini is used.\n";
		}

		if (in_array($this->options['-o'], ['tap', 'junit'], true)) {
			array_push($args, '-d', 'html_errors=off');
		}

		foreach ($this->options['-d'] as $item) {
			array_push($args, '-d', $item);
		}

		$this->interpreter = new PhpInterpreter($this->options['-p'], $args);

		if ($error = $this->interpreter->getStartupError()) {
			echo Dumper::color('red', "PHP startup error: $error") . "\n";
		}
	}


	/** @return Runner */
	private function createRunner()
	{
		$runner = new Runner($this->interpreter);
		$runner->paths = $this->options['paths'];
		$runner->threadCount = max(1, (int) $this->options['-j']);
		$runner->stopOnFail = $this->options['--stop-on-fail'];

		if ($this->options['--temp'] !== null) {
			$runner->setTempDirectory($this->options['--temp']);
		}

		if ($this->options['-o'] !== 'none') {
			switch ($this->options['-o']) {
				case 'tap':
					$runner->outputHandlers[] = new Output\TapPrinter;
					break;
				case 'junit':
					$runner->outputHandlers[] = new Output\JUnitPrinter;
					break;
				default:
					$runner->outputHandlers[] = new Output\ConsolePrinter($runner, (bool) $this->options['-s']);
			}
		}

		if ($this->options['--log']) {
			echo "Log: {$this->options['--log']}\n";
			$runner->outputHandlers[] = new Output\Logger($runner, $this->options['--log']);
		}

		if ($this->options['--setup']) {
			call_user_func(function () use ($runner) {
				require func_get_arg(0);
			}, $this->options['--setup']);
		}
		return $runner;
	}


	/** @return string */
	private function prepareCodeCoverage()
	{
		if (!$this->interpreter->canMeasureCodeCoverage()) {
			$alternative = PHP_VERSION_ID >= 70000 ? ' or phpdbg SAPI' : '';
			throw new \Exception("Code coverage functionality requires Xdebug extension$alternative (used {$this->interpreter->getCommandLine()})");
		}
		file_put_contents($this->options['--coverage'], '');
		$file = realpath($this->options['--coverage']);
		echo "Code coverage: {$file}\n";
		return $file;
	}


	/** @return void */
	private function finishCodeCoverage($file)
	{
		if (!in_array($this->options['-o'], ['none', 'tap', 'junit'], true)) {
			echo 'Generating code coverage report... ';
		}
		if (pathinfo($file, PATHINFO_EXTENSION) === 'xml') {
			$generator = new CodeCoverage\Generators\CloverXMLGenerator($file, $this->options['--coverage-src']);
		} else {
			$generator = new CodeCoverage\Generators\HtmlGenerator($file, $this->options['--coverage-src']);
		}
		$generator->render($file);
		echo round($generator->getCoveredPercent()) . "% covered\n";
	}


	/** @return void */
	private function watch(Runner $runner)
	{
		$prev = [];
		$counter = 0;
		while (true) {
			$state = [];
			foreach ($this->options['--watch'] as $directory) {
				foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
					if (substr($file->getExtension(), 0, 3) === 'php' && substr($file->getBasename(), 0, 1) !== '.') {
						$state[(string) $file] = md5_file((string) $file);
					}
				}
			}
			if ($state !== $prev) {
				$prev = $state;
				$runner->run();
			}
			echo 'Watching ' . implode(', ', $this->options['--watch']) . ' ' . str_repeat('.', ++$counter % 5) . "    \r";
			sleep(2);
		}
	}
}
