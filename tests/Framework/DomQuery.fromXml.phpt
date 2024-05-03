<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\DomQuery;

require __DIR__ . '/../bootstrap.php';


$xml = <<<'XML'
	<root>
		<item id="test1" class="foo">Item 1</item>
		<item id="test2" class="bar">Item 2</item>
		<container>
			<item id="test3" class="foo">Item 3</item>
		</container>
	</root>
	XML;

$dom = DomQuery::fromXml($xml);
Assert::type(DomQuery::class, $dom);

// root
Assert::true($dom->matches('root'));
Assert::false($dom->has('root'));

// find
$results = $dom->find('.foo');
Assert::count(2, $results);
Assert::type(DomQuery::class, $results[0]);
Assert::type(DomQuery::class, $results[1]);

// children
$results = $dom->find('> item');
Assert::count(2, $results);

// has
Assert::true($dom->has('#test1'));
Assert::false($dom->has('#nonexistent'));
Assert::false($dom->find('container')[0]->has('#test1'));
Assert::true($dom->find('container')[0]->has('#test3'));

// matches
$subItem = $dom->find('#test1')[0];
Assert::true($subItem->matches('.foo'));
Assert::false($subItem->matches('.bar'));
