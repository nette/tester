<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class Test
{
	private static $static = 123;

	private $nonstatic = 456;
}


Assert::with(Test::class, function () {
	Assert::same(123, self::$static);
});


$test = new Test;
Assert::with($test, function () {
	Assert::same(456, $this->nonstatic);
});
