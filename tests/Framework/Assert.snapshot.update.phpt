<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Environment;
use Tester\Helpers;
use Tester\Snapshot;

require __DIR__ . '/../bootstrap.php';

// https://bugs.php.net/bug.php?id=76801
// fixed by https://github.com/php/php-src/pull/3965 in PHP 7.2.18
if (strncasecmp(PHP_OS, 'win', 3) === 0 && strpos(PHP_BINARY, 'phpdbg') !== false && version_compare(PHP_VERSION, '7.2.18') < 0) {
	Environment::skip('There is a bug in PHP :(');
}

putenv(Environment::UPDATE_SNAPSHOTS . '=1');
Snapshot::$snapshotDir = __DIR__ . DIRECTORY_SEPARATOR . 'snapshots';
Helpers::purge(Snapshot::$snapshotDir);

// newly created

Assert::false(file_exists(Snapshot::$snapshotDir . DIRECTORY_SEPARATOR . 'Assert.snapshot.update.newSnapshot.phps'));
Assert::snapshot('newSnapshot', ['answer' => 42]);
Assert::true(file_exists(Snapshot::$snapshotDir . DIRECTORY_SEPARATOR . 'Assert.snapshot.update.newSnapshot.phps'));
Assert::contains('42', file_get_contents(Snapshot::$snapshotDir . DIRECTORY_SEPARATOR . 'Assert.snapshot.update.newSnapshot.phps'));

// existing

file_put_contents(
	Snapshot::$snapshotDir . DIRECTORY_SEPARATOR . 'Assert.snapshot.update.updatedSnapshot.phps',
	'<?php return array(\'answer\' => 43);' . PHP_EOL
);

Assert::true(file_exists(Snapshot::$snapshotDir . DIRECTORY_SEPARATOR . 'Assert.snapshot.update.updatedSnapshot.phps'));
Assert::snapshot('updatedSnapshot', ['answer' => 42]);
Assert::true(file_exists(Snapshot::$snapshotDir . DIRECTORY_SEPARATOR . 'Assert.snapshot.update.updatedSnapshot.phps'));
Assert::contains('42', file_get_contents(Snapshot::$snapshotDir . DIRECTORY_SEPARATOR . 'Assert.snapshot.update.updatedSnapshot.phps'));

// Snapshot::$updatedSnapshots

Assert::same([
	Snapshot::$snapshotDir . DIRECTORY_SEPARATOR . 'Assert.snapshot.update.newSnapshot.phps',
	Snapshot::$snapshotDir . DIRECTORY_SEPARATOR . 'Assert.snapshot.update.updatedSnapshot.phps',
], Snapshot::$updatedSnapshots);

// reset the env variable so that the test does not fail due to updated snapshots
putenv(Environment::UPDATE_SNAPSHOTS . '=0');
