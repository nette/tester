<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\DomQuery;

require __DIR__ . '/../bootstrap.php';

test('type selectors', function () {
	Assert::same('//*', DomQuery::css2xpath('*'));
	Assert::same('//foo', DomQuery::css2xpath('foo'));
});


test('#ID', function () {
	Assert::same("//*[@id='foo']", DomQuery::css2xpath('#foo'));
	Assert::same("//*[@id='id']", DomQuery::css2xpath('*#id'));
});


test('class', function () {
	Assert::same("//*[contains(concat(' ', normalize-space(@class), ' '), ' foo ')]", DomQuery::css2xpath('.foo'));
	Assert::same("//*[contains(concat(' ', normalize-space(@class), ' '), ' foo ')]", DomQuery::css2xpath('*.foo'));
	Assert::same("//*[contains(concat(' ', normalize-space(@class), ' '), ' foo ')][contains(concat(' ', normalize-space(@class), ' '), ' bar ')]", DomQuery::css2xpath('.foo.bar'));
});


test('attribute selectors', function () {
	Assert::same('//div[@foo]', DomQuery::css2xpath('div[foo]'));
	Assert::same("//div[@foo='bar']", DomQuery::css2xpath('div[foo=bar]'));
	Assert::same("//*[@foo='bar']", DomQuery::css2xpath('[foo="bar"]'));
	Assert::same("//div[@foo='bar']", DomQuery::css2xpath('div[foo="bar"]'));
	Assert::same("//div[@foo='bar']", DomQuery::css2xpath("div[foo='bar']"));
	Assert::same("//div[@foo='bar']", DomQuery::css2xpath('div[Foo="bar"]'));
	Assert::same("//div[contains(concat(' ', normalize-space(@foo), ' '), ' bar ')]", DomQuery::css2xpath('div[foo~="bar"]'));
	Assert::same("//div[contains(@foo, 'bar')]", DomQuery::css2xpath('div[foo*="bar"]'));
	Assert::same("//div[starts-with(@foo, 'bar')]", DomQuery::css2xpath('div[foo^="bar"]'));
	Assert::same("//div[substring(@foo, string-length(@foo)-0)='bar']", DomQuery::css2xpath('div[foo$="bar"]'));
	Assert::same("//div[@foo='bar[]']", DomQuery::css2xpath("div[foo='bar[]']"));
	Assert::same("//div[@foo='bar[]']", DomQuery::css2xpath('div[foo="bar[]"]'));
});


test('variants', function () {
	Assert::same("//*[@id='foo']|//*[@id='bar']", DomQuery::css2xpath('#foo, #bar'));
	Assert::same("//*[@id='foo']|//*[@id='bar']", DomQuery::css2xpath('#foo,#bar'));
	Assert::same("//*[@id='foo']|//*[@id='bar']", DomQuery::css2xpath('#foo ,#bar'));
});


test('descendant combinator', function () {
	Assert::same(
		"//div[@id='foo']//*[contains(concat(' ', normalize-space(@class), ' '), ' bar ')]",
		DomQuery::css2xpath('div#foo .bar'),
	);
	Assert::same(
		'//div//*//p',
		DomQuery::css2xpath('div * p'),
	);
});


test('child combinator', function () {
	Assert::same("//div[@id='foo']/span", DomQuery::css2xpath('div#foo>span'));
	Assert::same("//div[@id='foo']/span", DomQuery::css2xpath('div#foo > span'));
});


test('general sibling combinator', function () {
	Assert::same('//div/following-sibling::span', DomQuery::css2xpath('div ~ span'));
});


test('complex', function () {
	Assert::same(
		"//div[@id='foo']//span[contains(concat(' ', normalize-space(@class), ' '), ' bar ')]"
		. "|//*[@id='bar']//li[contains(concat(' ', normalize-space(@class), ' '), ' baz ')]//a",
		DomQuery::css2xpath('div#foo span.bar, #bar li.baz a'),
	);
});


test('pseudoclass', function () {
	Assert::exception(
		fn() => DomQuery::css2xpath('a:first-child'),
		InvalidArgumentException::class,
	);
});


test('adjacent sibling combinator', function () {
	Assert::exception(
		fn() => DomQuery::css2xpath('div + span'),
		InvalidArgumentException::class,
	);
});
