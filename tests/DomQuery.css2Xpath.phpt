<?php

use Tester\Assert,
	Tester\DomQuery;

require __DIR__ . '/bootstrap.php';

// type selectors
Assert::same("//*", DomQuery::css2xpath('*'));
Assert::same("//foo", DomQuery::css2xpath('foo'));

// #ID
Assert::same("//*[@id='foo']", DomQuery::css2xpath('#foo'));
Assert::same("//*[@id='id']", DomQuery::css2xpath('*#id'));

// class
Assert::same("//*[contains(concat(' ', normalize-space(@class), ' '), ' foo ')]", DomQuery::css2xpath('.foo'));
Assert::same("//*[contains(concat(' ', normalize-space(@class), ' '), ' foo ')]", DomQuery::css2xpath('*.foo'));
Assert::same("//*[contains(concat(' ', normalize-space(@class), ' '), ' foo ')][contains(concat(' ', normalize-space(@class), ' '), ' bar ')]", DomQuery::css2xpath('.foo.bar'));

// attribute selectors
Assert::same("//div[@foo]", DomQuery::css2xpath("div[foo]"));
Assert::same("//div[@foo='bar']", DomQuery::css2xpath('div[foo=bar]'));
Assert::same("//*[@foo='bar']", DomQuery::css2xpath('[foo="bar"]'));
Assert::same("//div[@foo='bar']", DomQuery::css2xpath('div[foo="bar"]'));
Assert::same("//div[@foo='bar']", DomQuery::css2xpath("div[foo='bar']"));
Assert::same("//div[@foo='bar']", DomQuery::css2xpath('div[Foo="bar"]'));
Assert::same("//div[contains(concat(' ', normalize-space(@foo), ' '), ' bar ')]", DomQuery::css2xpath('div[foo~="bar"]'));
Assert::same("//div[contains(@foo, 'bar')]", DomQuery::css2xpath('div[foo*="bar"]'));
Assert::same("//div[starts-with(@foo, 'bar')]", DomQuery::css2xpath('div[foo^="bar"]'));
Assert::same("//div[substring(@foo, string-length(@foo)-0)='bar']", DomQuery::css2xpath('div[foo$="bar"]'));

// variants
Assert::same("//*[@id='foo']|//*[@id='bar']", DomQuery::css2xpath('#foo, #bar'));
Assert::same("//*[@id='foo']|//*[@id='bar']", DomQuery::css2xpath('#foo,#bar'));
Assert::same("//*[@id='foo']|//*[@id='bar']", DomQuery::css2xpath('#foo ,#bar'));

// descendant combinator
Assert::same(
	"//div[@id='foo']//*[contains(concat(' ', normalize-space(@class), ' '), ' bar ')]",
	DomQuery::css2xpath('div#foo .bar')
);

// child combinator
Assert::same("//div[@id='foo']/span", DomQuery::css2xpath('div#foo>span'));
Assert::same("//div[@id='foo']/span", DomQuery::css2xpath('div#foo > span'));

// general sibling combinator
Assert::same("//div/following-sibling::span", DomQuery::css2xpath('div ~ span'));

// complex
Assert::same(
	"//div[@id='foo']//span[contains(concat(' ', normalize-space(@class), ' '), ' bar ')]"
	. "|//*[@id='bar']//li[contains(concat(' ', normalize-space(@class), ' '), ' baz ')]//a",
	DomQuery::css2xpath('div#foo span.bar, #bar li.baz a')
);

// pseudoclass
Assert::exception(function(){
	DomQuery::css2xpath('a:first-child');
}, 'InvalidArgumentException');

// adjacent sibling combinator
Assert::exception(function(){
	DomQuery::css2xpath('div + span');
}, 'InvalidArgumentException');
