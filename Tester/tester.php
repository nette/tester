 _____ ___  ___ _____ ___  ___
|Nette/ __)( __/_   _/ __)| _ )
  |_| \___ /___) |_| \___ |_|_\  v0.9.5

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


$cmd = new Cmd("Usage:
    tester.php [options] [<test file> | <directory>]...

Options:
    -p <path>            specify PHP executable to run (default: php-cgi)
    -c <path>            look for php.ini file (or look in directory) <path>
    -log <path>          write log to file <path>
    -d <key=value>...    define INI entry 'key' with value 'val'
    -s                   show information about skipped tests
    --tap                generate Test Anything Protocol
    -j <num>             run <num> jobs in parallel
    -w | --watch <path>  watch directory
    --setup <path>       script for runner setup
    --colors [1|0]       enable or disable colors
    -h | --help          this help

", array(
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
}

if ($cmd->isEmpty() || $options['--help']) {
	$cmd->help();
	exit;
}

$phpArgs = $options['-c'] ? '-n -c ' . escapeshellarg($options['-c']) : '-n';
foreach ($options['-d'] as $item) {
	$phpArgs .= ' -d ' . escapeshellarg($item);
}

$runner = new Tester\Runner\Runner(new Tester\Runner\PhpExecutable($options['-p'], $phpArgs));
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
