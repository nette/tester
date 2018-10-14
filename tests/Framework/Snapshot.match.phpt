<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\AssertException;
use Tester\Snapshot;

require __DIR__ . '/../bootstrap.php';

Snapshot::$snapshotDir = __DIR__ . '/fixtures';

Snapshot::match(['answer' => 42], 'existingSnapshot');

Assert::exception(function () {
	Snapshot::match(['answer' => 43], 'existingSnapshot');
}, AssertException::class, 'Snapshot existingSnapshot: %a% should be equal to %a%');

Assert::exception(function () {
	Snapshot::match('value', 'nonExistingSnapshot');
}, AssertException::class, "Missing snapshot file '%A%', use --update-snapshots option to generate it.");
