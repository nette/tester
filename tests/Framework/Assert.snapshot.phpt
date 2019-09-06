<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\AssertException;
use Tester\Snapshot;

require __DIR__ . '/../bootstrap.php';

Snapshot::$snapshotDir = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures';

Assert::snapshot('existingSnapshot', ['answer' => 42]);

Assert::exception(function () {
	Assert::snapshot('invalid / name', ['answer' => 42]);
}, \Exception::class, "Invalid snapshot name 'invalid / name'. Only alphanumeric characters, dash and underscore are allowed.");

Assert::exception(function () {
	Assert::snapshot('existingSnapshot', ['answer' => 42]);
}, \Exception::class, "Snapshot 'existingSnapshot' was already asserted, please use a different name.");

Assert::exception(function () {
	Assert::snapshot('anotherSnapshot', ['answer' => 43]);
}, AssertException::class, "%a% should be %a% in snapshot 'anotherSnapshot'");

Assert::exception(function () {
	Assert::snapshot('nonExistingSnapshot', 'value');
}, AssertException::class, "Missing snapshot 'nonExistingSnapshot', use --update-snapshots option to generate it.");
