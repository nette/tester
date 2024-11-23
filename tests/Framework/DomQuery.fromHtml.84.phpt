<?php

/**
 * @phpVersion 8.4
 */

declare(strict_types=1);

use Tester\Assert;
use Tester\DomQuery;

require __DIR__ . '/../bootstrap.php';


test('fromHtml() creates DomQuery from HTML string', function () {
	$dom = DomQuery::fromHtml('<div class="test"><p>Hello</p></div>');
	Assert::type(DomQuery::class, $dom);
	Assert::true($dom->has('div'));
});

test('fromHtml() handles HTML without root element', function () {
	$dom = DomQuery::fromHtml('Hello world');
	Assert::type(DomQuery::class, $dom);
	Assert::contains('Hello world', (string) $dom->find('body')[0]);
});

test('fromHtml() handles void elements correctly', function () {
	$dom = DomQuery::fromHtml('<div><source src="test.mp3"><wbr>test</div>');
	Assert::true($dom->has('source'));
	Assert::true($dom->has('wbr'));
});

test('fromHtml() handles script tags with </ inside', function () {
	$dom = DomQuery::fromHtml('<script>if (a</b) { alert("test"); }</script>');
	Assert::true($dom->has('script'));
});

test('find() returns matching elements', function () {
	$dom = DomQuery::fromHtml('
        <div class="container">
            <p class="first">First paragraph</p>
            <p class="second">Second paragraph</p>
            <span>Test span</span>
        </div>
    ');

	$paragraphs = $dom->find('p');
	Assert::count(2, $paragraphs);
	Assert::contains('First paragraph', (string) $paragraphs[0]);

	$spans = $dom->find('span');
	Assert::count(1, $spans);
	Assert::contains('Test span', (string) $spans[0]);
});

test('find() supports complex CSS selectors', function () {
	$dom = DomQuery::fromHtml('
        <div class="container">
            <p class="first">First</p>
            <div class="wrapper">
                <p class="second">Second</p>
                <p class="third">Third</p>
            </div>
        </div>
    ');

	$results = $dom->find('div.wrapper p');
	Assert::count(2, $results);
	Assert::contains('Second', (string) $results[0]);

	$results = $dom->find('p.first + div');
	Assert::count(1, $results);
	Assert::true($results[0]->has('p.second'));
});

test('has() checks for existence of elements', function () {
	$dom = DomQuery::fromHtml('
        <div class="test">
            <span class="inner">Test</span>
        </div>
    ');

	Assert::true($dom->has('span.inner'));
	Assert::true($dom->has('div.test'));
	Assert::false($dom->has('p'));
	Assert::false($dom->has('.nonexistent'));
});

test('matches() checks if element matches selector', function () {
	$dom = DomQuery::fromHtml('<div class="test"><p class="para">Test</p></div>');
	$para = $dom->find('p')[0];

	Assert::true($para->matches('p'));
	Assert::true($para->matches('.para'));
	Assert::true($para->matches('p.para'));
	Assert::false($para->matches('div'));
	Assert::false($para->matches('.test'));
});

test('find() returns empty array for no matches', function () {
	$dom = DomQuery::fromHtml('<div></div>');
	Assert::same([], $dom->find('nonexistent'));
});

test('handles malformed HTML gracefully', function () {
	Assert::error(function () use (&$dom) {
		$dom = DomQuery::fromHtml('<div><p>Unclosed paragraph<span>Test</div>');
	}, E_USER_WARNING, 'Tester\DomQuery::fromHtml: tree error unexpected-element-in-open-elements-stack%a%');
	Assert::true($dom->has('div'));
	Assert::true($dom->has('p'));
	Assert::true($dom->has('span'));
});

test('handles HTML entities in attributes', function () {
	$dom = DomQuery::fromHtml('<div data-test="&quot;quoted&quot;">Test</div>');
	Assert::true($dom->find('div')[0]->matches('[data-test="\\"quoted\\""]'));
});

test('handles UTF-8', function () {
	$q = DomQuery::fromHtml('<p>žluťoučký</p>');
	Assert::same('žluťoučký', (string) $q->find('p')[0]);
});
