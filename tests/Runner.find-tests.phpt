<?php

/**
 * @phpversion 5.4  Requires constant PHP_BINARY available since PHP 5.4.0
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/TestHandler.php';
require __DIR__ . '/../Tester/Runner/Runner.php';


$runner = new Tester\Runner\Runner(createInterpreter());

if (defined('HHVM_VERSION')) {
	$rm = new ReflectionMethod(get_class($runner), 'findTests');
	$rm->setAccessible(TRUE);
	$rm->invoke($runner, __DIR__ . '/find-tests/*.phptx');

	$rp = new ReflectionProperty(get_class($runner), 'jobs');
	$rp->setAccessible(TRUE);
	$jobs = $rp->getValue($runner);
	$rp->setAccessible(FALSE);

	Assert::count(1, $jobs);

} else {
	Assert::with($runner, function() {
		$this->findTests(__DIR__ . '/find-tests/*.phptx');
		Assert::count(1, $this->jobs);
	});
}
