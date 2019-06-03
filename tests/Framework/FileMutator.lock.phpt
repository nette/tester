<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Tester\Environment::bypassFinals();

Assert::noError(function () {
	file_put_contents(__DIR__ . '/fixtures/tmp', 'foo', LOCK_EX);
});
