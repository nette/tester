<?php declare(strict_types=1);

use Tester\{Assert, Environment};

require __DIR__ . '/../bootstrap.php';


// waiting for the lock must not consume the test's time limit (on Windows it measures real time),
// but the limit has to be restored afterwards
ini_set('max_execution_time', '30');

Environment::lock('environment-lock-' . getmypid(), sys_get_temp_dir());

Assert::same('30', ini_get('max_execution_time'));


// an already held lock returns immediately and still leaves the limit alone
Environment::lock('environment-lock-' . getmypid(), sys_get_temp_dir());

Assert::same('30', ini_get('max_execution_time'));


testException(
	'empty lock directory is refused',
	fn() => Environment::lock('name'),
	RuntimeException::class,
	'Lock directory is not specified.',
);


testException(
	'missing lock directory is refused',
	fn() => Environment::lock('name', __DIR__ . '/missing-dir'),
	RuntimeException::class,
	"Lock directory '%a%missing-dir' is missing.",
);


test('lock file is created in given directory', function () {
	$dir = Tester\Helpers::prepareTempDir(sys_get_temp_dir());
	Environment::lock('foo', $dir);
	Assert::true(is_file($dir . '/lock-' . md5('foo')));
});
