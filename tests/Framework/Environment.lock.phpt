<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Environment;

require __DIR__ . '/../bootstrap.php';


// waiting for the lock must not consume the test's time limit (on Windows it measures real time),
// but the limit has to be restored afterwards
ini_set('max_execution_time', '30');

Environment::lock('environment-lock-' . getmypid(), sys_get_temp_dir());

Assert::same('30', ini_get('max_execution_time'));


// an already held lock returns immediately and still leaves the limit alone
Environment::lock('environment-lock-' . getmypid(), sys_get_temp_dir());

Assert::same('30', ini_get('max_execution_time'));
