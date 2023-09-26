<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::noError(
	fn() => null, // no error there
);

Assert::exception(
	fn() => Assert::noError(
		fn() => $a = &pi(),
	),
	Tester\AssertException::class,
	'Generated more errors than expected: E_NOTICE %a%',
);


Assert::exception(
	fn() => Assert::noError(
		fn() => throw new Exception('Unexpected'),
	),
	Exception::class,
	'Unexpected',
);


Assert::exception(
	fn() => Assert::noError(
		fn() => throw new Exception('Unexpected'),
		InvalidArgumentException::class,
	),
	Exception::class,
	'Tester\Assert::noError() expects 1 parameter, 2 given.',
);
