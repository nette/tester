<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';


(new Tester\TestCaseRunner)
	->findTests(__DIR__ . '/Test*.php')
	->run();
