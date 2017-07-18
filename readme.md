[Nette Tester](https://tester.nette.org): enjoyable unit testing
===============================================================

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/tester.svg)](https://packagist.org/packages/nette/tester)
[![Build Status](https://travis-ci.org/nette/tester.svg?branch=master)](https://travis-ci.org/nette/tester)
[![Build Status Windows](https://ci.appveyor.com/api/projects/status/github/nette/tester?branch=master&svg=true)](https://ci.appveyor.com/project/dg/tester/branch/master)
[![Latest Stable Version](https://poser.pugx.org/nette/tester/v/stable)](https://github.com/nette/tester/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/nette/tester/blob/master/license.md)

Nette Tester is a productive and enjoyable unit testing framework. It's used by
the [Nette Framework](https://nette.org) and is capable of testing any PHP code.


Installation
------------

The best way how to install is to [download a latest package](https://github.com/nette/tester/releases)
or use a Composer:

```
php composer.phar require --dev nette/tester
```

Nette Tester requires PHP 5.4.0 or later. Collecting and processing
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
require 'src/bootstrap.php';

use Tester\Assert;

$h = new Greeting;

// use an assertion function to test say()
Assert::same( 'Hello John', $h->say('John') );
```

Thats' all!

Now we run tests from command-line using the `tester` command:

```
> tester
 _____ ___  ___ _____ ___  ___
|_   _/ __)( __/_   _/ __)| _ )
  |_| \___ /___) |_| \___ |_|_\  v2.0.x

PHP 5.4.0 | "php-cgi" -n | 8 threads
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
- `Assert::true($value)` - Reports an error if $value is not true.
- `Assert::false($value)` - Reports an error if $value is not false.
- `Assert::truthy($value)` - Reports an error if $value is not truthy.
- `Assert::falsey($value)` - Reports an error if $value is not falsey.
- `Assert::null($value)` - Reports an error if $value is not null.
- `Assert::nan($value)` - Reports an error if $value is not NAN.
- `Assert::type($type, $value)` -  Reports an error if the variable $value is not of PHP or class type $type.
- `Assert::exception($closure, $class, $message = null, $code = null)` -  Checks if the function throws exception.
- `Assert::error($closure, $level, $message = null)` -  Checks if the function $closure throws PHP warning/notice/error.
- `Assert::noError($closure)` -  Checks that the function $closure does not throw PHP warning/notice/error or exception.
- `Assert::match($pattern, $value)` - Compares result using regular expression or mask.
- `Assert::matchFile($file, $value)` - Compares result using regular expression or mask sorted in file.
- `Assert::count($count, $value)` - Reports an error if number of items in $value is not $count.

Testing exceptions:

```php
Assert::exception(function () {
	$h = new Greeting;
	$h->say(null);
}, 'InvalidArgumentException', 'Invalid name.');
```

Testing PHP errors, warnings or notices:


```php
Assert::error(function () {
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

Running tests in parallel is very much faster and Nette Tester uses 8 threads as default.
If you wish to run the tests in series use:

```
tester -j 1
```

How do you find code that is not yet tested? Use Code-Coverage Analysis. This feature
requires you have installed [Xdebug](http://xdebug.org/) in `php.ini`. This will
generate nice HTML report in `coverage.html`.

```
tester . -c php.ini --coverage coverage.html --coverage-src /my/source/codes
```

We can load Nette Tester using Composer's autoloader. In this case
it is important to setup Nette Tester environment:

```php
require 'vendor/autoload.php';

Tester\Environment::setup();
```

We can also test HTML pages. Let the [template engine](https://latte.nette.org) generate
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
    -p <path>                    Specify PHP executable to run (default: php-cgi).
    -c <path>                    Look for php.ini file (or look in directory) <path>.
    -l | --log <path>            Write log to file <path>.
    -d <key=value>...            Define INI entry 'key' with value 'val'.
    -s                           Show information about skipped tests.
    --stop-on-fail               Stop execution upon the first failure.
    -j <num>                     Run <num> jobs in parallel (default: 8).
    -o <console|tap|junit|none>  Specify output format.
    -w | --watch <path>          Watch directory.
    -i | --info                  Show tests environment info and exit.
    --setup <path>               Script for runner setup.
    --colors [1|0]               Enable or disable colors.
    --coverage <path>            Generate code coverage report to file.
    --coverage-src <path>        Path to source code.
    -h | --help                  This help.
```
