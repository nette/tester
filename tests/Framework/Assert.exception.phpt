<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$e = Assert::exception(
	fn() => throw new Exception,
	Exception::class,
);

Assert::true($e instanceof Exception);

Assert::exception(
	fn() => throw new Exception('Text 123'),
	Exception::class,
	'Text %d%',
);

Assert::exception(
	fn() => eval('*'),
	Error::class,
	'syntax error%a?%',
);

Assert::exception(
	fn() => Assert::exception(
		fn() => null,
		Exception::class,
	),
	Tester\AssertException::class,
	'Exception was expected, but none was thrown',
);

$obj = new stdClass;
$e = Assert::exception(
	fn() => Assert::exception(
		fn() => throw $obj->e = new Exception('message'),
		'UnknownException',
	),
	Tester\AssertException::class,
	'UnknownException was expected but got Exception (message)',
);
Assert::same($obj->e, $e->getPrevious());

$obj = new stdClass;
$e = Assert::exception(
	fn() => Assert::exception(
		fn() => throw $obj->e = new Exception('Text'),
		Exception::class,
		'Abc',
	),
	Tester\AssertException::class,
	"Exception with a message matching 'Abc' was expected but got 'Text'",
);
Assert::same($obj->e, $e->getPrevious());

Assert::exception(
	fn() => throw new Exception('Text', 42),
	Exception::class,
	null,
	42,
);

$obj = new stdClass;
$e = Assert::exception(
	fn() => Assert::exception(
		fn() => throw $obj->e = new Exception('Text', 1),
		Exception::class,
		null,
		42,
	),
	Tester\AssertException::class,
	'Exception with a code 42 was expected but got 1',
);
Assert::same($obj->e, $e->getPrevious());

$old = Assert::$onFailure;
Assert::$onFailure = function () {};
$e = Assert::exception(function () {}, Exception::class);
Assert::$onFailure = $old;
Assert::null($e);
