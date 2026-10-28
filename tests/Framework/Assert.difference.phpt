<?php declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class Entity
{
	public ?Entity $parent = null;


	public function __construct(
		public int $id,
		private array $data,
	) {
	}
}


// nested arrays
Assert::exception(
	fn() => Assert::same(['status' => 'ok', 'fields' => ['a', 'b', 'c']], ['status' => 'ok', 'fields' => ['a', 'x', 'c']]),
	Tester\AssertException::class,
	"['status' => 'ok', 'fields' => [...]] should be ['status' => 'ok', 'fields' => [...]]\n    at ['fields'][1]: 'x' should be 'b'",
);

Assert::exception(
	fn() => Assert::equal(['a' => ['b' => ['c' => 1]]], ['a' => ['b' => ['c' => 2]]]),
	Tester\AssertException::class,
	"['a' => [...]] should be equal to ['a' => [...]]\n    at ['a']['b']['c']: 2 should be 1",
);

// long list
Assert::exception(
	fn() => Assert::same(range(1, 100), array_replace(range(1, 100), [57 => 0])),
	Tester\AssertException::class,
	"%a% should be %a%\n    at [57]: 0 should be 58",
);

// missing and unexpected keys
Assert::exception(
	fn() => Assert::same(['a' => ['x' => 1, 'y' => 2]], ['a' => ['x' => 1]]),
	Tester\AssertException::class,
	"%a%\n    at ['a']['y']: key is missing",
);

Assert::exception(
	fn() => Assert::equal(['a' => ['x' => 1]], ['a' => ['x' => 1, 'y' => 2]]),
	Tester\AssertException::class,
	"%a%\n    at ['a']['y']: key is unexpected",
);

// different order of keys
Assert::exception(
	fn() => Assert::same(['list' => ['a' => 1, 'b' => 2]], ['list' => ['b' => 2, 'a' => 1]]),
	Tester\AssertException::class,
	"%a%\n    at ['list']: ['b' => 2, 'a' => 1] should be ['a' => 1, 'b' => 2]",
);

// long strings show the place of difference
Assert::exception(
	fn() => Assert::same(['body' => str_repeat('lorem ipsum ', 10) . 'END'], ['body' => str_repeat('lorem ipsum ', 10) . 'FIN']),
	Tester\AssertException::class,
	"%a%\n    at ['body']: '...%a% FIN' should be '...%a% END'",
);

// placeholders inside values are kept
Assert::exception(
	fn() => Assert::same(['url' => ['a%201', 'b']], ['url' => ['a%202', 'b']]),
	Tester\AssertException::class,
	"%a%\n    at ['url'][0]: 'a%%202' should be 'a%%201'",
);

// properties of objects are compared by equal()
Assert::exception(
	fn() => Assert::equal([new Entity(1, ['x' => 'a'])], [new Entity(1, ['x' => 'b'])]),
	Tester\AssertException::class,
	"[Entity(%a%)] should be equal to [Entity(%a%)]\n    at [0]->data['x']: 'b' should be 'a'",
);

Assert::exception(
	function () {
		$expected = new Entity(1, []);
		$expected->parent = new Entity(2, []);
		$expected->parent->parent = $expected;
		$actual = new Entity(1, []);
		$actual->parent = new Entity(3, []);
		$actual->parent->parent = $actual;
		Assert::equal($expected, $actual);
	},
	Tester\AssertException::class,
	"%a%\n    at ->parent->id: 3 should be 2",
);

// identity of objects is compared by same() and equal() with $matchIdentity
Assert::exception(
	fn() => Assert::same([new Entity(1, [])], [new Entity(1, [])]),
	Tester\AssertException::class,
	'[Entity(%a%)] should be [Entity(%a%)]',
);

Assert::exception(
	fn() => Assert::equal([new Entity(1, [])], [new Entity(1, [])], matchIdentity: true),
	Tester\AssertException::class,
	'[Entity(%a%)] should be equal to [Entity(%a%)]',
);

// nothing is added when the dump shows the difference
Assert::exception(
	fn() => Assert::same(['a' => 1, 'b' => 2], ['a' => 1, 'b' => 3]),
	Tester\AssertException::class,
	"['a' => 1, 'b' => 3] should be ['a' => 1, 'b' => 2]",
);
