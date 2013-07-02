<?php

/**
 * This file is part of the Nette Tester.
 *
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Tester\Runner;


/**
 * CLI layer.
 *
 * @author     David Grudl
 */
class CliFactory
{

	/**
	 * @return Runner
	 */
	public function createRunner()
	{
		$phpExec = 'php-cgi';
		$phpArgs = array();
		$paths = array();
		$iniSet = $logFile = $displaySkipped = FALSE;
		$jobs = 1;

		$args = new \ArrayIterator(array_slice(isset($_SERVER['argv']) ? $_SERVER['argv'] : array(), 1));
		foreach ($args as $arg) {
			if (!preg_match('#^[-/][a-z]+\z#', $arg)) {
				if ($path = realpath($arg)) {
					$paths[] = $path;
				} else {
					throw new \Exception("Invalid path '$arg'.");
				}

			} else switch (substr($arg, 1)) {
				case 'p':
					$args->next();
					$phpExec = $args->current();
					break;
				case 'log':
					$args->next();
					$logFile = $args->current();
					echo "Log: $logFile\n";
					break;
				case 'c':
					$args->next();
					$path = realpath($args->current());
					if ($path === FALSE) {
						throw new \Exception("PHP configuration file '{$args->current()}' not found.");
					}
					$phpArgs['c'] = '-c ' . escapeshellarg($path);
					$iniSet = TRUE;
					break;
				case 'd':
					$args->next();
					$phpArgs[] = '-d ' . escapeshellarg($args->current());
					break;
				case 's':
					$displaySkipped = TRUE;
					break;
				case 'j':
					$args->next();
					$jobs = max(1, (int) $args->current());
					break;
				default:
					throw new \Exception("Unknown option $arg.");
					exit;
			}
		}

		if (!$iniSet) {
			$phpArgs[] = '-n';
		}

		$runner = new Runner(new PhpExecutable($phpExec, implode(' ', $phpArgs)), $logFile);
		$runner->paths = $paths ?: array(getcwd());
		$runner->displaySkipped = $displaySkipped;
		$runner->jobs = $jobs;
		return $runner;
	}


	/**
	 * @return void
	 */
	public function showHelp()
	{
		echo "
Usage:
	php tester.php [options] [file or directory]

Options:
	-p <php>    Specify PHP-CGI executable to run.
	-c <path>   Look for php.ini in directory <path> or use <path> as php.ini.
	-log <path> Write log to file <path>.
	-d key=val  Define INI entry 'key' with value 'val'.
	-s          Show information about skipped tests.
	-j <num>    Run <num> jobs in parallel.

";
	}

}
