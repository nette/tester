<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$cases = array(
	"Failed: array(TRUE) should contain 1" => function() { Assert::contains(1, array(TRUE)); },
	"Failed: array('') should not contain ''" => function() { Assert::notContains('', array('')); },
	'Failed: 1.0 should be equal to 1' => function() { Assert::equal(1, 1.0); },
	"Failed: 0.33%d% should not be equal to 0.33%d%" => function() { Assert::notEqual(1/3, 1 - 2/3); },
	"Failed: NULL should be FALSE" => function() { Assert::false(NULL); },
	"Failed: FALSE should be TRUE" => function() { Assert::true(FALSE); },
	"Failed: 0 should be NULL" => function() { Assert::null(0); },
	"Failed: array('b' => FALSE, 'a' => TRUE) should be falsey" => function() { Assert::falsey(array('b' => FALSE, 'a' => TRUE)); },
	"Failed: SimpleXMLElement(#%a%) should be truthy" => function() { Assert::truthy(new SimpleXMLElement('<xml></xml>')); },
	"Failed: stdClass(#%a%) should be stdClass(#%a%)" => function() { Assert::same(new stdClass, new stdClass); },
	"Failed: NULL should not be NULL" => function() { Assert::notSame(NULL, NULL); },
	"Failed: boolean should be instance of x" => function() { Assert::type('x', TRUE); },
	"Failed: resource should be int" => function() { Assert::type('int', fopen(__FILE__, 'r')); },
	"Failed: 'Hello\nWorld' should match\n    ... '%a%'" => function() { Assert::match('%a%', "Hello\nWorld"); },
	"Failed: '...xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' should be \n    ... '...xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'" => function() { Assert::same(str_repeat('x', 100), str_repeat('x', 120)); },
);

foreach ($cases as $message => $closure) {
	$e = Assert::exception($closure, 'Tester\AssertException');
	Assert::match($message . "\n%A%", Tester\Dumper::removeColors(Tester\Dumper::dumpException($e)));
}
