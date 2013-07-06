<?php

/**
 * Code coverage HTML report generator.
 *
 * This file is part of the Nette Tester.
 */


require __DIR__ . '/CodeCoverage/ReportGenerator.php';
require __DIR__ . '/Runner/CommandLine.php';

use Tester\Runner\CommandLine as Cmd;


$cmd = new Cmd("
Code coverage HTML report generator
-----------------------------------

Usage:
	php coverage-report.php [options]

Options:
	-c <path>    coverage.dat file (default: coverage.dat)
	-s <path>    directory with source code
	-o <path>    output file (default: coverage.html)
	-t <title>   title of generated documentation
	-h | --help  this help

", array(
	'-c' => array(Cmd::REALPATH),
	'-s' => array(Cmd::REALPATH),
));


$options = $cmd->parse();

if ($cmd->isEmpty()) {
	$cmd->help();
} elseif ($options['--help']) {
	$cmd->help();
	exit;
}

try {
	$generator = new ReportGenerator($options['-c'], $options['-s'], $options['-t']);
	if ($options['-o'] === '-') {
		$generator->render();
	} else {
		echo "Generating report to {$options['-o']}\n";
		$generator->render($options['-o']);
		echo "Done.\n";
	}

} catch (Exception $e) {
	echo "Error: {$e->getMessage()}\n";
	die(254);
}
