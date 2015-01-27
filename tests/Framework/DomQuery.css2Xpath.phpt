<?php

use Tester\Assert,
	Tester\DomQuery;

require __DIR__ . '/../bootstrap.php';

test(function() { // type selectors
	Assert::same("//*", DomQuery::css2xpath('*'));
	Assert::same("//foo", DomQuery::css2xpath('foo'));
});


test(function() { // #ID
	Assert::same("//*[@id='foo']", DomQuery::css2xpath('#foo'));
	Assert::same("//*[@id='id']", DomQuery::css2xpath('*#id'));
});


test(function() { // class
	Assert::same("//*[contains(concat(' ', normalize-space(@class), ' '), ' foo ')]", DomQuery::css2xpath('.foo'));
	Assert::same("//*[contains(concat(' ', normalize-space(@class), ' '), ' foo ')]", DomQuery::css2xpath('*.foo'));
	Assert::same("//*[contains(concat(' ', normalize-space(@class), ' '), ' foo ')][contains(concat(' ', normalize-space(@class), ' '), ' bar ')]", DomQuery::css2xpath('.foo.bar'));
});


test(function() { // attribute selectors
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
});


test(function() { // variants
	Assert::same("//*[@id='foo']|//*[@id='bar']", DomQuery::css2xpath('#foo, #bar'));
	Assert::same("//*[@id='foo']|//*[@id='bar']", DomQuery::css2xpath('#foo,#bar'));
	Assert::same("//*[@id='foo']|//*[@id='bar']", DomQuery::css2xpath('#foo ,#bar'));
});


test(function() { // descendant combinator
	Assert::same(
		"//div[@id='foo']//*[contains(concat(' ', normalize-space(@class), ' '), ' bar ')]",
		DomQuery::css2xpath('div#foo .bar')
	);
});


test(function() { // child combinator
	Assert::same("//div[@id='foo']/span", DomQuery::css2xpath('div#foo>span'));
	Assert::same("//div[@id='foo']/span", DomQuery::css2xpath('div#foo > span'));
});


test(function() { // general sibling combinator
	Assert::same("//div/following-sibling::span", DomQuery::css2xpath('div ~ span'));
});


test(function() { // complex
	Assert::same(
		"//div[@id='foo']//span[contains(concat(' ', normalize-space(@class), ' '), ' bar ')]"
		. "|//*[@id='bar']//li[contains(concat(' ', normalize-space(@class), ' '), ' baz ')]//a",
		DomQuery::css2xpath('div#foo span.bar, #bar li.baz a')
	);
});


test(function() { // pseudoclass
	Assert::exception(function() {
		DomQuery::css2xpath('a:first-child');
	}, 'InvalidArgumentException');
});


test(function() { // adjacent sibling combinator
	Assert::exception(function() {
		DomQuery::css2xpath('div + span');
	}, 'InvalidArgumentException');
});
