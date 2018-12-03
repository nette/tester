<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Environment;
use Tester\Helpers;
use Tester\Snapshot;

require __DIR__ . '/../bootstrap.php';

putenv(Environment::UPDATE_SNAPSHOTS . '=1');
Snapshot::$snapshotDir = __DIR__ . '/snapshots';
Helpers::purge(Snapshot::$snapshotDir);

// newly created

Assert::false(file_exists(Snapshot::$snapshotDir . '/Assert.snapshot.update.newSnapshot.phps'));
Assert::snapshot('newSnapshot', ['answer' => 42]);
Assert::true(file_exists(Snapshot::$snapshotDir . '/Assert.snapshot.update.newSnapshot.phps'));
Assert::contains('42', file_get_contents(Snapshot::$snapshotDir . '/Assert.snapshot.update.newSnapshot.phps'));

// existing

file_put_contents(
	Snapshot::$snapshotDir . '/Assert.snapshot.update.updatedSnapshot.phps',
	'<?php return array(\'answer\' => 43);' . PHP_EOL
);

Assert::true(file_exists(Snapshot::$snapshotDir . '/Assert.snapshot.update.updatedSnapshot.phps'));
Assert::snapshot('updatedSnapshot', ['answer' => 42]);
Assert::true(file_exists(Snapshot::$snapshotDir . '/Assert.snapshot.update.updatedSnapshot.phps'));
Assert::contains('42', file_get_contents(Snapshot::$snapshotDir . '/Assert.snapshot.update.updatedSnapshot.phps'));

// Snapshot::$updatedSnapshots

Assert::same([
	Snapshot::$snapshotDir . DIRECTORY_SEPARATOR . 'Assert.snapshot.update.newSnapshot.phps',
	Snapshot::$snapshotDir . DIRECTORY_SEPARATOR . 'Assert.snapshot.update.updatedSnapshot.phps',
], Snapshot::$updatedSnapshots);

// reset the env variable so that the test does not fail due to updated snapshots
putenv(Environment::UPDATE_SNAPSHOTS . '=0');
