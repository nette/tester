<?php

/**
 * Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester;

use Tester\Runner\CommandLine as Cmd;

require __DIR__ . '/Runner/PhpInterpreter.php';
require __DIR__ . '/Runner/ZendPhpInterpreter.php';
require __DIR__ . '/Runner/HhvmPhpInterpreter.php';
require __DIR__ . '/Runner/Runner.php';
require __DIR__ . '/Runner/Job.php';
require __DIR__ . '/Runner/CommandLine.php';
require __DIR__ . '/Runner/TestHandler.php';
require __DIR__ . '/Runner/OutputHandler.php';
require __DIR__ . '/Runner/Output/Logger.php';
require __DIR__ . '/Runner/Output/TapPrinter.php';
require __DIR__ . '/Runner/Output/ConsolePrinter.php';
require __DIR__ . '/Framework/Helpers.php';
require __DIR__ . '/Framework/Environment.php';
require __DIR__ . '/Framework/Assert.php';
require __DIR__ . '/Framework/Dumper.php';
require __DIR__ . '/Framework/DataProvider.php';
require __DIR__ . '/Framework/TestCase.php';
require __DIR__ . '/CodeCoverage/ReportGenerator.php';


$tester = new Tester;
die($tester->run());


/** @internal */
class Tester
{
	/** @var array */
	private $options;

	/** @var Runner\PhpInterpreter */
	private $interpreter;


	/** @return int|NULL */
	public function run()
	{
		Environment::setupColors();
		Environment::setupErrors();

		ob_start();
		$cmd = $this->loadOptions();

		Environment::$debugMode = (bool) $this->options['--debug'];
		if (isset($this->options['--colors'])) {
			Environment::$useColors = (bool) $this->options['--colors'];
		} elseif ($this->options['-o'] === 'tap') {
			Environment::$useColors = FALSE;
		}

		if ($cmd->isEmpty() || $this->options['--help']) {
			$cmd->help();
			return;
		}

		$this->createPhpInterpreter();

		if ($this->options['--info']) {
			$job = new Runner\Job(__DIR__ . '/Runner/info.php', $this->interpreter);
			$job->run();
			echo $job->getOutput();
			return;
		}

		if ($this->options['--coverage']) {
			$coverageFile = $this->prepareCodeCoverage();
		}

		$runner = $this->createRunner();

		if ($this->options['-o'] !== NULL) {
			ob_clean();
		}
		ob_end_flush();

		if ($this->options['--watch']) {
			$this->watch($runner);
			return;
		}

		$result = $runner->run();

		if (isset($coverageFile)) {
			$this->finishCodeCoverage($coverageFile);
		}

		return $result ? 0 : 1;
	}


	/** @return Runner\CommandLine */
	private function loadOptions()
	{
		echo <<<XX
 _____ ___  ___ _____ ___  ___
|_   _/ __)( __/_   _/ __)| _ )
  |_| \___ /___) |_| \___ |_|_\  v1.2.0


XX;

		$cmd = new Cmd(<<<XX
Usage:
    tester.php [options] [<test file> | <directory>]...

Options:
    -p <path>              Specify PHP interpreter to run (default: php-cgi).
    -c <path>              Look for php.ini file (or look in directory) <path>.
    -l | --log <path>      Write log to file <path>.
    -d <key=value>...      Define INI entry 'key' with value 'val'.
    -s                     Show information about skipped tests.
    --stop-on-fail         Stop execution upon the first failure.
    -j <num>               Run <num> jobs in parallel (default: 33).
    -o <console|tap|none>  Specify output format.
    -w | --watch <path>    Watch directory.
    -i | --info            Show tests environment info and exit.
    --setup <path>         Script for runner setup.
    --colors [1|0]         Enable or disable colors.
    --coverage <path>      Generate code coverage report to file.
    --coverage-src <path>  Path to source code.
    -h | --help            This help.

XX
		, array(
			'-c' => array(Cmd::REALPATH => TRUE),
			'--watch' => array(Cmd::REPEATABLE => TRUE, Cmd::REALPATH => TRUE),
			'--setup' => array(Cmd::REALPATH => TRUE),
			'paths' => array(Cmd::REPEATABLE => TRUE, Cmd::VALUE => getcwd()),
			'--debug' => array(),
			'--coverage-src' => array(Cmd::REALPATH => TRUE),
		));

		if (isset($_SERVER['argv'])) {
			if ($tmp = array_search('-log', $_SERVER['argv'])) {
				$_SERVER['argv'][$tmp] = '--log';
			}

			if ($tmp = array_search('--tap', $_SERVER['argv'])) {
				unset($_SERVER['argv'][$tmp]);
				$_SERVER['argv'] = array_merge($_SERVER['argv'], array('-o', 'tap'));
			}
		}

		$this->options = $cmd->parse();
		return $cmd;
	}


	/** @return void */
	private function createPhpInterpreter()
	{
		$phpArgs = '';
		if ($this->options['-c']) {
			$phpArgs .= ' -c ' . Helpers::escapeArg($this->options['-c']);
		} elseif (!$this->options['--info']) {
			echo "Note: No php.ini is used.\n";
		}

		foreach ($this->options['-d'] as $item) {
			$phpArgs .= ' -d ' . Helpers::escapeArg($item);
		}

		// analyze whether to use PHP or HHVM
		$proc = @proc_open(
			$this->options['-p'] . " --php $phpArgs --version", // --version must be the last
			array(array('pipe', 'r'), array('pipe', 'w'), array('pipe', 'w')),
			$pipes,
			NULL,
			NULL,
			array('bypass_shell' => TRUE)
		);
		$output = stream_get_contents($pipes[1]);
		$error = stream_get_contents($pipes[2]);
		if (proc_close($proc)) {
			throw new \Exception("Unable to run '" . $this->options['-p'] . "': " . preg_replace('#[\r\n ]+#', ' ', $error));
		}

		if (preg_match('#HipHop VM#', $output)) {
			$this->interpreter = new Runner\HhvmPhpInterpreter($this->options['-p'], $phpArgs);
			echo "Using HHVM\n";
		} elseif (stripos($output, 'PHP') !== FALSE) {
			$this->interpreter = new Runner\ZendPhpInterpreter($this->options['-p'], $phpArgs);
		} else {
			throw new \Exception("Unable to detect whether binary is PHP or HHVM. ($output).");
		}
	}


	/** @return Runner\Runner */
	private function createRunner()
	{
		$runner = new Runner\Runner($this->interpreter);
		$runner->paths = $this->options['paths'];
		$runner->threadCount = max(1, (int) $this->options['-j']);
		$runner->stopOnFail = $this->options['--stop-on-fail'];

		if ($this->options['-o'] !== 'none') {
			$runner->outputHandlers[] = $this->options['-o'] === 'tap'
				? new Runner\Output\TapPrinter($runner)
				: new Runner\Output\ConsolePrinter($runner, $this->options['-s']);
		}

		if ($this->options['--log']) {
			echo "Log: {$this->options['--log']}\n";
			$runner->outputHandlers[] = new Runner\Output\Logger($runner, $this->options['--log']);
		}

		if ($this->options['--setup']) {
			call_user_func(function() use ($runner) {
				require func_get_arg(0);
			}, $this->options['--setup']);
		}
		return $runner;
	}


	/** @return string */
	private function prepareCodeCoverage()
	{
		if (!$this->interpreter->hasXdebug()) {
			throw new \Exception("Code coverage functionality requires Xdebug extension (used {$this->interpreter->getCommandLine()})");
		}
		file_put_contents($this->options['--coverage'], '');
		$file = realpath($this->options['--coverage']);
		putenv(Environment::COVERAGE . '=' . $file);
		echo "Code coverage: {$file}\n";
		if (preg_match('#\.html?\z#', $file)) {
			return $file;
		}
	}


	/** @return void */
	private function finishCodeCoverage($file)
	{
		if ($this->options['-o'] !== 'none' && $this->options['-o'] !== 'tap') {
			echo "Generating code coverage report\n";
		}
		$generator = new CodeCoverage\ReportGenerator($file, $this->options['--coverage-src']);
		$generator->render($file);
	}


	/** @return void */
	private function watch($runner)
	{
		$prev = array();
		$counter = 0;
		while (TRUE) {
			$state = array();
			foreach ($this->options['--watch'] as $directory) {
				foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
					if (substr($file->getExtension(), 0, 3) === 'php') {
						$state[(string) $file] = md5_file((string) $file);
					}
				}
			}
			if ($state !== $prev) {
				$prev = $state;
				$runner->run();
			}
			echo "Watching " . implode(', ', $this->options['--watch']) . " " . str_repeat('.', ++$counter % 5) . "    \r";
			sleep(2);
		}
	}

}
