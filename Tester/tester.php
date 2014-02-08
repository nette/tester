<?php

/**
 * Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */


require __DIR__ . '/Runner/PhpExecutable.php';
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

use Tester\Runner\CommandLine as Cmd;


Tester\Environment::setup();

ob_start();
echo <<<XX
 _____ ___  ___ _____ ___  ___
|_   _/ __)( __/_   _/ __)| _ )
  |_| \___ /___) |_| \___ |_|_\  v1.0.0


XX;

$cmd = new Cmd(<<<XX
Usage:
    tester.php [options] [<test file> | <directory>]...

Options:
    -p <path>            Specify PHP executable to run (default: php-cgi).
    -c <path>            Look for php.ini file (or look in directory) <path>.
    -log <path>          Write log to file <path>.
    -d <key=value>...    Define INI entry 'key' with value 'val'.
    -s                   Show information about skipped tests.
    --tap                Generate Test Anything Protocol.
    -j <num>             Run <num> jobs in parallel.
    -w | --watch <path>  Watch directory.
    -i | --info          Show tests environment info and exit.
    --setup <path>       Script for runner setup.
    --colors [1|0]       Enable or disable colors.
    -h | --help          This help.

XX
, array(
	'-c' => array(Cmd::REALPATH => TRUE),
	'--watch' => array(Cmd::REPEATABLE => TRUE, Cmd::REALPATH => TRUE),
	'--setup' => array(Cmd::REALPATH => TRUE),
	'paths' => array(Cmd::REPEATABLE => TRUE, Cmd::VALUE => getcwd()),
	'--debug' => array(),
));


$options = $cmd->parse();

Tester\Environment::$debugMode = (bool) $options['--debug'];

if (isset($options['--colors'])) {
	Tester\Environment::$useColors = (bool) $options['--colors'];
} elseif ($options['--tap']) {
	Tester\Environment::$useColors = FALSE;
}

if ($cmd->isEmpty() || $options['--help']) {
	$cmd->help();
	exit;
}

$phpArgs = '-n';
if ($options['-c']) {
	$phpArgs .= ' -c ' . Tester\Helpers::escapeArg($options['-c']);
} elseif (!$options['--info']) {
	echo "Note: No php.ini is used.\n";
}

foreach ($options['-d'] as $item) {
	$phpArgs .= ' -d ' . Tester\Helpers::escapeArg($item);
}

$php = new Tester\Runner\PhpExecutable($options['-p'], $phpArgs);

if ($options['--info']) {
	$job = new Tester\Runner\Job(__DIR__ . '/Runner/info.php', $php);
	$job->run();
	echo $job->getOutput();
	exit;
}

$runner = new Tester\Runner\Runner($php);
$runner->paths = $options['paths'];
$runner->threadCount = max(1, (int) $options['-j']);

$runner->outputHandlers[] = $options['--tap']
	? new Tester\Runner\Output\TapPrinter($runner)
	: new Tester\Runner\Output\ConsolePrinter($runner, $options['-s']);

if ($options['-log']) {
	echo "Log: {$options['-log']}\n";
	$runner->outputHandlers[] = new Tester\Runner\Output\Logger($runner, $options['-log']);
}

if ($options['--setup']) {
	call_user_func(function() use ($runner) {
		require func_get_arg(0);
	}, $options['--setup']);
}


if ($options['--tap']) {
	ob_end_clean();
} else {
	ob_end_flush();
}

@unlink(__DIR__ . '/coverage.dat'); // @ - file may not exist

if (!$options['--watch']) {
	die($runner->run() ? 0 : 1);
}

$prev = array();
$counter = 0;
while (TRUE) {
	$state = array();
	foreach ($options['--watch'] as $directory) {
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
			if (substr($file->getExtension(), 0, 3) === 'php') {
				$state[(string) $file] = md5_file((string) $file);
			}
		}
	}
	if ($state !== $prev) {
		$prev = $state;
		$runner->run();
	}
	echo "Watching " . implode(', ', $options['--watch']) . " " . str_repeat('.', ++$counter % 5) . "    \r";
	sleep(2);
}
