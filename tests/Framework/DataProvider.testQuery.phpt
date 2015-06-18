<?php

use Tester\Assert;
use Tester\DataProvider;

require __DIR__ . '/../bootstrap.php';


Assert::true(DataProvider::testQuery('foo 1.2.3 yyy', ''));
Assert::true(DataProvider::testQuery('foo 1.2.3 yyy', 'foo,'));
Assert::false(DataProvider::testQuery('foo 1.2.3 yyy', 'bar'));
Assert::false(DataProvider::testQuery('foo 1.2.3 yyy', 'xxx'));
Assert::true(DataProvider::testQuery('foo 1.2.3 yyy', '> cc'));
Assert::true(DataProvider::testQuery('foo 1.2.3 yyy', 'foo, < 10'));
Assert::false(DataProvider::testQuery('foo 1.2.3 yyy', 'foo, >= 2.2.3'));
Assert::true(DataProvider::testQuery('foo 1.2.3 yyy', 'foo, >= 1.2.3, != xxx'));
Assert::true(DataProvider::testQuery('foo 1.2.3 yyy', 'foo, >= 1.2.3, = yyy'));
Assert::true(DataProvider::testQuery('foo 2.2.3', ''));
Assert::true(DataProvider::testQuery('foo 2.2.3', 'foo,'));
Assert::false(DataProvider::testQuery('foo 2.2.3', 'bar'));
Assert::false(DataProvider::testQuery('foo 2.2.3', 'xxx'));
Assert::true(DataProvider::testQuery('foo 2.2.3', '> cc'));
Assert::true(DataProvider::testQuery('foo 2.2.3', 'foo, < 10'));
Assert::true(DataProvider::testQuery('foo 2.2.3', 'foo, >= 2.2.3'));
Assert::true(DataProvider::testQuery('foo 2.2.3', 'foo, >= 1.2.3, != xxx'));
Assert::false(DataProvider::testQuery('foo 2.2.3', 'foo, >= 1.2.3, = yyy'));
Assert::true(DataProvider::testQuery('foo 3 xxx', ''));
Assert::true(DataProvider::testQuery('foo 3 xxx', 'foo,'));
Assert::false(DataProvider::testQuery('foo 3 xxx', 'bar'));
Assert::false(DataProvider::testQuery('foo 3 xxx', 'xxx'));
Assert::true(DataProvider::testQuery('foo 3 xxx', '> cc'));
Assert::true(DataProvider::testQuery('foo 3 xxx', 'foo, < 10'));
Assert::true(DataProvider::testQuery('foo 3 xxx', 'foo, >= 2.2.3'));
Assert::false(DataProvider::testQuery('foo 3 xxx', 'foo, >= 1.2.3, != xxx'));
Assert::false(DataProvider::testQuery('foo 3 xxx', 'foo, >= 1.2.3, = yyy'));
Assert::true(DataProvider::testQuery('bar 1.2.3', ''));
Assert::false(DataProvider::testQuery('bar 1.2.3', 'foo,'));
Assert::true(DataProvider::testQuery('bar 1.2.3', 'bar'));
Assert::false(DataProvider::testQuery('bar 1.2.3', 'xxx'));
Assert::false(DataProvider::testQuery('bar 1.2.3', '> cc'));
Assert::false(DataProvider::testQuery('bar 1.2.3', 'foo, < 10'));
Assert::false(DataProvider::testQuery('bar 1.2.3', 'foo, >= 2.2.3'));
Assert::false(DataProvider::testQuery('bar 1.2.3', 'foo, >= 1.2.3, != xxx'));
Assert::false(DataProvider::testQuery('bar 1.2.3', 'foo, >= 1.2.3, = yyy'));
Assert::true(DataProvider::testQuery('bar', ''));
Assert::false(DataProvider::testQuery('bar', 'foo,'));
Assert::true(DataProvider::testQuery('bar', 'bar'));
Assert::false(DataProvider::testQuery('bar', 'xxx'));
Assert::false(DataProvider::testQuery('bar', '> cc'));
Assert::false(DataProvider::testQuery('bar', 'foo, < 10'));
Assert::false(DataProvider::testQuery('bar', 'foo, >= 2.2.3'));
Assert::false(DataProvider::testQuery('bar', 'foo, >= 1.2.3, != xxx'));
Assert::false(DataProvider::testQuery('bar', 'foo, >= 1.2.3, = yyy'));
