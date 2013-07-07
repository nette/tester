<?php

/**
 * Nette Tester (version 0.9-dev)
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
require __DIR__ . '/Framework/DataProvider.php';

use Tester\Runner\CommandLine as Cmd;


Tester\Helpers::setup(FALSE);


$cmd = new Cmd("
Nette Tester (v0.9)
-------------------

Usage:
	tester.php [options] [<test file> | <directory>]...

Options:
	-p <path>        Specify PHP-CGI executable to run.
	-c <path>        Look for php.ini in directory <path> or use <path> as php.ini.
	-log <path>      Write log to file <path>.
	-d <key=val>...  Define INI entry 'key' with value 'val'.
	-s               Show information about skipped tests.
	-j <num>         Run <num> jobs in parallel.
	-h | --help      This help.

", array(
	'-p' => array(Cmd::REALPATH => TRUE),
	'-c' => array(Cmd::REALPATH => TRUE),
	'paths' => array(Cmd::REALPATH => TRUE, Cmd::REPEATABLE => TRUE, Cmd::VALUE => getcwd()),
));


$options = $cmd->parse();

if ($cmd->isEmpty()) {
	$cmd->help();
} elseif ($options['--help']) {
	$cmd->help();
	exit;
}

$phpArgs = $options['-c'] ? '-c ' . escapeshellarg($options['-c']) : '-n';
foreach ($options['-d'] as $item) {
	$phpArgs .= ' -d ' . escapeshellarg($item);
}

$runner = new Tester\Runner\Runner(new Tester\Runner\PhpExecutable($options['-p'] ?: 'php-cgi', $phpArgs), $options['-log']);
$runner->paths = $options['paths'];
$runner->displaySkipped = $options['-s'];
$runner->jobs = max(1, (int) $options['-j']);



@unlink(__DIR__ . '/coverage.dat'); // @ - file may not exist

die($runner->run() ? 0 : 1);
