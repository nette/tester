[Nette Tester](http://tester.nette.org) - Testing framework
===========================================================

Nette Tester is a powerful, easy-to-use unit testing framework. It's used by
the [Nette Framework](http://nette.org) and is capable of testing any PHP code.


Installation
------------

The best way how to install is to [download a latest package](https://github.com/nette/tester/releases)
or use a Composer:

```
php composer.phar require --dev nette/tester
```

Nette Tester requires PHP 5.3.0 or later. Collecting and processing
code coverage information depends on Xdebug.


Writing Tests
-------------

Imagine that we are testing this simple class:

```php
class Greeting
{
	function say($name)
	{
		if (!$name) {
			throw new InvalidArgumentException('Invalid name.');
		}
		return "Hello $name";
	}
}
```

So we create test file named `greeting.test.phpt`:

```php
require 'Tester/bootstrap.php';

use Tester\Assert;

$h = new Greeting;

// use an assertion function to test say()
Assert::same( 'Hello John', $h->say('John') );
```

Thats' all!

Now we run tests from command-line using the `tester` command:

```
> tester

Nette Tester (v0.9.3)
---------------------

PHP 5.3.16 | "php-cgi" -n | 1 threads
.
OK (1 tests, 0 skipped, 0.0 seconds)
```

Nette Tester prints dot for successful test, F for failed test
and S when the test has been skipped.

Assertions
----------

This table shows all assertions (class `Assert` means `Tester\Assert`):

- `Assert::same($expected, $actual)` - Reports an error if $expected and $actual are not the same.
- `Assert::notSame($expected, $actual)` - Reports an error if $expected and $actual are the same.
- `Assert::equal($expected, $actual)` - Like same(), but identity of objects and the order of keys in the arrays are ignored.
- `Assert::notEqual($expected, $actual)` - Like notSame(), but identity of objects and arrays order are ignored.
- `Assert::contains($needle, array $haystack)` - Reports an error if $needle is not an element of $haystack.
- `Assert::contains($needle, string $haystack)` - Reports an error if $needle is not a substring of $haystack.
- `Assert::notContains($needle, array $haystack)` - Reports an error if $needle is an element of $haystack.
- `Assert::notContains($needle, string $haystack)` - Reports an error if $needle is a substring of $haystack.
- `Assert::true($actual)` - Reports an error if $actual is not TRUE.
- `Assert::false($actual)` - Reports an error if $actual is not FALSE.
- `Assert::null($actual)` - Reports an error if $actual is not NULL.
- `Assert::type($type, $actual)` -  Reports an error if the variable $actual is not of type $type.
- `Assert::exception($closure, $class, $message = NULL)` -  Checks if the function throws exception.
- `Assert::error($closure, $level, $message = NULL)` -  Checks if the function $closure throws PHP warning/notice/error.
- `Assert::match($expected, $actual)` - Compares variables $expected and $actual using mask.

Testing exceptions:

```php
Assert::exception(function() {
	$h = new Greeting;
	$h->say(NULL);
}, 'InvalidArgumentException', 'Invalid name.');
```

Testing PHP errors, warnings or notices:


```php
Assert::error(function() {
	$h = new Greeting;
	echo $h->abc;
}, E_NOTICE, 'Undefined property: Greeting::$abc');
```

Tips and features
-----------------

Running unit tests manually is annoying, so let Nette Tester to watch your folder
with code and automatically re-run tests whenever code is changed:

```
tester -w /my/source/codes
```

Running tests is very much faster when they are executed in parallel. Let Nette Tester
to use 40 threads:

```
tester -j 40
```

How do you find code that is not yet tested? Use Code-Coverage Analysis. This feature
requires you have installed [Xdebug](http://xdebug.org/). Add this line to the begin
of your test file (after Nette Tester is loaded):

```php
Tester\CodeCoverage\Collector::start(__DIR__ . '/coverage.dat');
```

And run tests. It will generate `coverage.dat`. Using command-line tool `coverage-report.php`
we can generate nice HTML report:

```
coverage-report.php -c coverage.dat -o coverage.html -s /my/source/codes
```

We can load Nette Tester using Composer's autoloader. In this case
it is important to setup Nette Tester environment:

```php
require 'vendor/autoload.php';

Tester\Helpers::setup();
```

We can also test HTML pages. Let the [template engine](http://latte.nette.org) generate
HTML code or download existing page to `$html` variable. We will check whether
the page contains form fields for username and password. The syntax is the
same as the CSS selectors:

```php
$dom = Tester\DomQuery::fromHtml($html);

Assert::true( $dom->has('input[name="username"]') );
Assert::true( $dom->has('input[name="password"]') );
```

For more inspiration see how [Nette Tester tests itself](https://github.com/nette/tester/tree/master/tests).


Running tests
-------------

The command-line test runner can be invoked through the `tester` command (or `php tester.php`). Take a look
at the command-line options:

```
> tester

Usage:
	tester.php [options] [<test file> | <directory>]...

Options:
	-p <path>            Specify PHP executable to run (default: php-cgi).
	-c <path>            Look for php.ini in directory <path> or use <path> as php.ini.
	-log <path>          Write log to file <path>.
	-d <key=value>...    Define INI entry 'key' with value 'val'.
	-s                   Show information about skipped tests.
	--tap                Generate Test Anything Protocol.
	-j <num>             Run <num> jobs in parallel.
	-w | --watch <path>  Watch directory.
	--colors [1|0]       Enable or disable colors.
	-h | --help          This help.
```

-----

[![Build Status](https://secure.travis-ci.org/nette/tester.png?branch=master)](http://travis-ci.org/nette/tester)
