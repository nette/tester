<?php declare(strict_types=1);

use Tester\Assert;
use Tester\DomQuery;

require __DIR__ . '/../bootstrap.php';


// expectations shared by the native CSS engine (PHP 8.4+) and the XPath fallback
$q = DomQuery::fromXml('<r><a><i/></a><b name="foobar" title="it&apos;s" data="a&apos;b&quot;c"/></r>');


test('selector list is limited to the element', function () use ($q) {
	$a = $q->find('a')[0];
	Assert::count(1, $a->find('i, b'));
	Assert::count(2, $q->find('i, b'));
	Assert::true($q->find('b')[0]->matches('i, b'));
	Assert::false($a->matches('i, b'));
});


test('attribute suffix', function () use ($q) {
	Assert::count(1, $q->find('[name$="bar"]'));
	Assert::count(1, $q->find('[name$="foobar"]'));
	Assert::count(0, $q->find('[name$="foo"]'));
});


test('attribute values with quotes', function () use ($q) {
	Assert::count(1, $q->find('[title="it\'s"]'));
	Assert::count(1, $q->find('[title^="it\'"]'));
	Assert::count(1, $q->find('[data*=\'b"c\']'));
	Assert::count(1, $q->find('[data$=\'b"c\']'));
});
