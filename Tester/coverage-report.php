<?php

/**
 * Code coverage HTML report generator.
 *
 * This file is part of the Nette Tester.
 */


require_once __DIR__ . '/CodeCoverage/ReportGenerator.php';


echo '
Code coverage HTML report generator
-----------------------------------
';

$options = (array) getopt('c:s:o:t:h', array('help'));

if (!$options) { ?>
Usage:
	php coverage-report.php [options]

Options:
	-c <path>  coverage.dat file (default is coverage.dat)
	-s <path>  directory with source code
	-o <path>  output file (default is coverage.html)
	-t ...     title of generated documentation

<?php
}


$options += array(
	'c' => 'coverage.dat',
	's' => NULL,
	'o' => 'coverage.html',
	't' => NULL,
);

try {
	$generator = new ReportGenerator($options['c'], $options['s'], $options['t']);
	if ($options['o'] === '-') {
		$generator->render();
	} else {
		echo "Generating report to $options[o]\n";
		$generator->render($options['o']);
		echo "Done.\n";
	}

} catch (Exception $e) {
	echo "Fatal error: {$e->getMessage()}\n";
	die(254);
}
