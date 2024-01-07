<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::error(
	fn() => $a = &pi(),
	E_NOTICE,
);

Assert::error(
	fn() => $a = &pi(),
	'E_NOTICE',
);

Assert::error(
	fn() => $a = &pi(),
	E_NOTICE,
	'Only variables should be assigned by reference',
);

Assert::error(
	function () {
		$a = &pi();
		@$a++;
		$a = &pi();
	},
	[
		[E_NOTICE, 'Only variables should be assigned by reference'],
		[E_NOTICE, 'Only variables should be assigned by reference'],
	],
);

Assert::error(
	function () {
		$a = &pi();
		$a = &pi();
	},
	[
		[E_NOTICE],
		[E_NOTICE],
	],
);

Assert::error(
	function () {
		$a = &pi();
		$a = &pi();
	},
	[E_NOTICE, E_NOTICE],
);

Assert::exception(
	fn() => Assert::error(
		fn() => null,
		E_NOTICE,
	),
	Tester\AssertException::class,
	'Error was expected, but was not generated',
);

Assert::exception(
	fn() => Assert::error(
		fn() => $a = &pi(),
		E_WARNING,
	),
	Tester\AssertException::class,
	'E_WARNING was expected, but E_NOTICE (Only variables should be assigned by reference) was generated in file %a% on line %d%',
);

Assert::exception(
	fn() => Assert::error(
		fn() => $a = &pi(),
		E_NOTICE,
		'Abc',
	),
	Tester\AssertException::class,
	"E_NOTICE with a message matching 'Abc' was expected but got 'Only variables should be assigned by reference'",
);

Assert::exception(
	fn() => Assert::error(
		function () {
			$a = &pi();
			$a = &pi();
		},
		E_NOTICE,
		'Only variables should be assigned by reference',
	),
	Tester\AssertException::class,
	'Generated more errors than expected: E_NOTICE (Only variables should be assigned by reference) was generated in file %a% on line %d%',
);

Assert::exception(
	fn() => Assert::error(
		fn() => $a = &pi(),
		[
			[E_NOTICE, 'Only variables should be assigned by reference'],
			[E_NOTICE, 'Only variables should be assigned by reference'],
		],
	),
	Tester\AssertException::class,
	'Error was expected, but was not generated',
);



$e = Assert::error(
	fn() => throw new Exception,
	Exception::class,
);

Assert::true($e instanceof Exception);

Assert::error(
	fn() => throw new Exception('Text 123'),
	Exception::class,
	'Text %d%',
);
