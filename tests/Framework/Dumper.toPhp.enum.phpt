<?php

/**
 * @phpVersion 8.1
 */

declare(strict_types=1);

use Tester\Assert;
use Tester\Dumper;

require __DIR__ . '/../bootstrap.php';


enum Enum
{
	case A;
	case B;
}


Assert::match('Enum::A', Dumper::toPhp(Enum::A));
Assert::match('Enum::B', Dumper::toPhp(Enum::B));

Assert::match('[Enum::A, Enum::A]', Dumper::toPhp([
	Enum::A,
	Enum::A,
]));
