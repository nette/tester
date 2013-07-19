<?php

/**
 * Nette Tester.
 *
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */


require __DIR__ . '/Runner/PhpExecutable.php';
require __DIR__ . '/Runner/Runner.php';
require __DIR__ . '/Runner/Job.php';
require __DIR__ . '/Runner/CommandLine.php';
require __DIR__ . '/Framework/Helpers.php';
require __DIR__ . '/Framework/Environment.php';
require __DIR__ . '/Framework/Assert.php';
require __DIR__ . '/Framework/Dumper.php';
require __DIR__ . '/Framework/DataProvider.php';

use Tester\Runner\CommandLine as Cmd;


Tester\Environment::setup();


$cmd = new Cmd("
Nette Tester (v0.9.1)
---------------------
Usage:
	tester.php [options] [<test file> | <directory>]...

Options:
	-p <path>        Specify PHP executable to run (default: php-cgi).
	-c <path>        Look for php.ini in directory <path> or use <path> as php.ini.
	-log <path>      Write log to file <path>.
	-d <key=val>...  Define INI entry 'key' with value 'val'.
	-s               Show information about skipped tests.
	-j <num>         Run <num> jobs in parallel.
	-w <path>        Watch directory.
	--colors [1|0]   Enable or disable colors.
	-h | --help      This help.

", array(
	'-c' => array(Cmd::REALPATH => TRUE),
	'-w' => array(Cmd::REALPATH => TRUE),
	'paths' => array(Cmd::REALPATH => TRUE, Cmd::REPEATABLE => TRUE, Cmd::VALUE => getcwd()),
	'--debug' => array(),
));


$options = $cmd->parse();

Tester\Environment::$debugMode = (bool) $options['--debug'];

if ($cmd->isEmpty()) {
	$cmd->help();
} elseif ($options['--help']) {
	$cmd->help();
	exit;
}

if (isset($options['--colors'])) {
	putenv('NETTE_TESTER_COLORS=' . (int) $options['--colors']);
} elseif (getenv('NETTE_TESTER_COLORS') === FALSE && (getenv('ConEmuANSI') === 'ON' || getenv('ANSICON') !== FALSE)) {
	putenv('NETTE_TESTER_COLORS=1');
}

$phpArgs = $options['-c'] ? '-c ' . escapeshellarg($options['-c']) : '-n';
foreach ($options['-d'] as $item) {
	$phpArgs .= ' -d ' . escapeshellarg($item);
}

$runner = new Tester\Runner\Runner(new Tester\Runner\PhpExecutable($options['-p'], $phpArgs), $options['-log']);
$runner->paths = $options['paths'];
$runner->displaySkipped = $options['-s'];
$runner->jobs = max(1, (int) $options['-j']);



@unlink(__DIR__ . '/coverage.dat'); // @ - file may not exist

if (!$options['-w']) {
	die($runner->run() ? 0 : 1);
}

$prev = array();
$counter = 0;
while (TRUE) {
	$state = array();
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($options['-w'])) as $file) {
		if (substr($file->getExtension(), 0, 3) === 'php') {
			$state[(string) $file] = md5_file((string) $file);
		}
	}
	if ($state !== $prev) {
		$prev = $state;
		$runner->run();
		echo "\n";
	}
	echo "Watching {$options['-w']} " . str_repeat('.', ++$counter % 5) . "    \r";
	sleep(2);
}
