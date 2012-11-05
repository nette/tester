<?php

/**
 * Test environment initialization.
 *
 * @author     David Grudl
 */

require __DIR__ . '/Framework/Helpers.php';
require __DIR__ . '/Framework/Assert.php';
require __DIR__ . '/Framework/TestCase.php';
require __DIR__ . '/Runner/Job.php';


// configure PHP environment
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', TRUE);
ini_set('html_errors', FALSE);
ini_set('log_errors', FALSE);


// catch errors/warnings/notices
set_error_handler(function($severity, $message, $file, $line) {
	if (($severity & error_reporting()) === $severity) {
		$e = new ErrorException($message, 0, $severity, $file, $line);
		echo "\nError: $message in $file:$line\nStack trace:\n" . $e->getTraceAsString();
		exit(Tester\Runner\Job::CODE_ERROR);
	}
	return FALSE;
});


// catch exceptions
set_exception_handler(function($e) {
	echo "\n" . ($e instanceof Tester\AssertException ? '' : get_class($e) . ': ') . $e->getMessage();
	$trace = $e->getTrace();
	while (isset($trace[0]['file']) && substr($trace[0]['file'], strlen(__DIR__))  === __DIR__) {
		array_shift($trace);
	}
	while ($trace) {
		if (isset($trace[0]['file'], $trace[0]['line'])) {
			echo "\nin " . implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $trace[0]['file']), -3)) . ':' . $trace[0]['line'];
		}
		array_shift($trace);
	}
	exit($e instanceof Tester\AssertException ? Tester\Runner\Job::CODE_FAIL : Tester\Runner\Job::CODE_ERROR);
});
