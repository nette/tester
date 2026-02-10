<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../vendor/autoload.php';

use Nette\PHPStan\Tester\TypeAssert;

TypeAssert::assertTypes(__DIR__ . '/tester-types.php');
