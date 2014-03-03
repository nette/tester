<?php

namespace Tester\Runner;

use Tester\Helpers;

/**
 * @author Michael Moravec
 */
class InterpreterFactory
{

	/**
	 * @param string
	 * @return IPhpInterpreter
	 */
	public function create($path)
	{
		$descriptors = array(array('pipe', 'r'), array('pipe', 'w'), array('pipe', 'w'));
		$proc = @proc_open(Helpers::escapeArg($path) . " --version", $descriptors, $pipes);
		$output = stream_get_contents($pipes[1]);
		$error = stream_get_contents($pipes[2]);
		if (proc_close($proc)) {
			throw new \Exception("Unable to run '$path': " . preg_replace('#[\r\n ]+#', ' ', $error));
		}

		if (preg_match('~HipHop~i', $output)) {
			//  PHP version of HHVM must be obtained another way...

			$proc = @proc_open(Helpers::escapeArg($path) . ' --php -r "echo PHP_VERSION;"', $descriptors, $pipes);
			$output = stream_get_contents($pipes[1]);
			$error = stream_get_contents($pipes[2]);
			if (proc_close($proc)) {
				throw new \Exception("Unable to run '$path': " . preg_replace('#[\r\n ]+#', ' ', $error));
			}

			return new HhvmExecutable($path, trim($output));
		}

		if (!preg_match('#^PHP (\S+).*c(g|l)i#i', $output, $matches)) {
			throw new \Exception("Unable to detect PHP version (output: $output).");
		}

		return new ZendPhpExecutable($path, $matches[1], strcasecmp($matches[2], 'g') === 0);
	}

}
