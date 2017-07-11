<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$cases = [
	'Failed: [true] should contain 1' => function () { Assert::contains(1, [true]); },
	"Failed: [''] should not contain ''" => function () { Assert::notContains('', ['']); },
	'Failed: 1.0 should be equal to 1' => function () { Assert::equal(1, 1.0); },
	'Failed: 0.33%d% should not be equal to 0.33%d%' => function () { Assert::notEqual(1 / 3, 1 - 2 / 3); },
	'Failed: null should be false' => function () { Assert::false(null); },
	'Failed: false should be true' => function () { Assert::true(false); },
	'Failed: 0 should be null' => function () { Assert::null(0); },
	"Failed: ['b' => false, 'a' => true] should be falsey" => function () { Assert::falsey(['b' => false, 'a' => true]); },
	'Failed: SimpleXMLElement(#%a%) should be truthy' => function () { Assert::truthy(new SimpleXMLElement('<xml></xml>')); },
	'Failed: stdClass(#%a%) should be stdClass(#%a%)' => function () { Assert::same(new stdClass, new stdClass); },
	'Failed: null should not be null' => function () { Assert::notSame(null, null); },
	'Failed: boolean should be instance of x' => function () { Assert::type('x', true); },
	'Failed: resource should be int' => function () { Assert::type('int', fopen(__FILE__, 'r')); },
	"Failed: 'Hello\\n\nWorld' should match\n    ... 'Hello'" => function () { Assert::match('%a%', "Hello\nWorld"); },
	"Failed: '...xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' should be \n    ... '...xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'" => function () { Assert::same(str_repeat('x', 100), str_repeat('x', 120)); },
	"Failed: '...xxxxxxxxxxxxxxxxxxxxxxxxxxx****************************************' should be \n    ... '...xxxxxxxxxxxxxxxxxxxxxxxxxxx'" => function () { Assert::same(str_repeat('x', 30), str_repeat('x', 30) . str_repeat('*', 40)); },
	"Failed: 'xxxxx*****************************************************************...' should be \n    ... 'xxxxx'" => function () { Assert::same(str_repeat('x', 5), str_repeat('x', 5) . str_repeat('*', 90)); },
	"Failed: '...xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx*' should be \n    ... '...xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'" => function () { Assert::same(str_repeat('x', 70), str_repeat('x', 70) . '*'); },
];

foreach ($cases as $message => $closure) {
	$e = Assert::exception($closure, Tester\AssertException::class);
	Assert::match($message . "\n%A%", Tester\Dumper::removeColors(Tester\Dumper::dumpException($e)));
}
