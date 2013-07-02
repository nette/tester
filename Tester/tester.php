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
require __DIR__ . '/Runner/CliFactory.php';
require __DIR__ . '/Framework/Helpers.php';
require __DIR__ . '/Framework/DataProvider.php';


echo "
Nette Tester (v0.9)
-------------------
";

Tester\Helpers::setup();

$cli = new Tester\Runner\CliFactory;

if (!isset($_SERVER['argc']) || $_SERVER['argc'] === 1) {
	$cli->showHelp();
} elseif (in_array('-h', $_SERVER['argv']) || in_array('--help', $_SERVER['argv'])) {
	$cli->showHelp();
	exit;
}

@unlink(__DIR__ . '/coverage.dat'); // @ - file may not exist
die($cli->createRunner()->run() ? 0 : 1);
