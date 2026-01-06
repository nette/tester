# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Nette Tester is a lightweight, standalone PHP testing framework designed for simplicity, speed, and process isolation. It runs tests in parallel by default (8 threads) and supports code coverage through Xdebug, PCOV, or PHPDBG.

**Key characteristics:**
- Zero external dependencies (pure PHP 8.0+)
- Each test runs in a completely isolated PHP process
- Annotation-driven test configuration
- Self-hosting (uses itself for testing)

## Essential Commands

```bash
# or directly:
src/tester tests -s -C

# Run specific test file
src/tester tests/Framework/Assert.phpt -s -C

# or simply
php tests/Framework/Assert.phpt

# Static analysis
composer run phpstan
# or directly:
vendor/bin/phpstan analyse
```

**Common test runner options:**
- `-s` - Show information about skipped tests
- `-C` - Use system-wide php.ini
- `-c <path>` - Use specific php.ini file
- `-d key=value` - Set PHP INI directive
- `-j <num>` - Number of parallel jobs (default: 8, use 1 for serial)
- `-p <path>` - Specify PHP interpreter to use
- `--stop-on-fail` - Stop execution on first failure
- `-o <format>` - Output format (can specify multiple):
  - `console` - Default format without ASCII logo
  - `console-lines` - One test per line with details
  - `tap` - Test Anything Protocol format
  - `junit` - JUnit XML format (e.g., `-o junit:output.xml`)
  - `log` - All tests including successful ones
  - `none` - No output
- `-i, --info` - Show test environment information and exit
- `--setup <path>` - Script to run at startup (has access to `$runner`)
- `--temp <path>` - Custom temporary directory
- `--colors [1|0]` - Force enable/disable colors

## Architecture

### Three-Layer Design

**Runner Layer** (`src/Runner/`)
- Test orchestration and parallel execution management
- `Runner` - Main test orchestrator, discovers tests, creates jobs
- `Job` - Wraps each test, spawns isolated PHP process via `proc_open()`
- `Test` - Immutable value object representing test state
- `TestHandler` - Processes test annotations and determines test variants
- `PhpInterpreter` - Encapsulates PHP binary configuration
- `Output/` - Multiple output formats (Console, TAP, JUnit, Logger)

**Framework Layer** (`src/Framework/`)
- Core testing utilities and assertions
- `Assert` - 25+ assertion methods (same, equal, exception, match, etc.)
- `TestCase` - xUnit-style base class with setUp/tearDown hooks
- `Environment` - Test environment initialization and configuration
- `DataProvider` - External test data loading (INI, PHP files)
- `Helpers` - Utility functions including annotation parsing

**Code Coverage Layer** (`src/CodeCoverage/`)
- Multi-engine coverage support (PCOV, Xdebug, PHPDBG)
- `Collector` - Aggregates coverage data from parallel test processes
- `Generators/` - HTML and CloverXML report generators

### Process Isolation Architecture

The most distinctive feature is **true process isolation**:

```
Runner (main process)
  ├── Job #1 → proc_open() → Isolated PHP process
  ├── Job #2 → proc_open() → Isolated PHP process
  └── Job #N → proc_open() → Isolated PHP process
```

**Why this matters:**
- Tests cannot interfere with each other (no shared state)
- Memory leaks don't accumulate across tests
- Fatal errors in one test don't crash the entire suite
- Parallel execution is straightforward and reliable

### Test Lifecycle State Machine

```
PREPARED (0) → [execution] → PASSED (2) | FAILED (1) | SKIPPED (3)
```

**Three phases:**
1. **Initiate** - Process annotations, create test variants (data providers, TestCase methods)
2. **Execute** - Run test in isolated process, capture output/exit code
3. **Assess** - Evaluate results against expectations (exit code, output patterns)

### Annotation-Driven Configuration

Tests use PHPDoc annotations for declarative configuration:

```php
/**
 * @phpVersion >= 8.1
 * @phpExtension json, mbstring
 * @dataProvider data.ini
 * @outputMatch %A%test passed%A%
 * @testCase
 */
```

The `TestHandler` class has dedicated `initiate*` and `assess*` methods for each annotation type.

## Testing Patterns

### Three Ways to Organize Tests

**Style 1: Simple assertion tests** (`.phpt` files)
```php
<?php
require __DIR__ . '/../bootstrap.php';

use Tester\Assert;

Assert::same('expected', $actual);
Assert::exception(
	fn() => $obj->method(),
	InvalidArgumentException::class,
	'Error message'
);
```
- Direct execution of assertions
- Good for unit tests and edge cases
- Fast and simple

**Style 2: Using test() function** (requires `Environment::setupFunctions()`)
```php
<?php
require __DIR__ . '/../bootstrap.php';

test('general rectangle', function () {
	$rect = new Rectangle(10, 20);
	Assert::same(200.0, $rect->getArea());
});

test('dimensions must not be negative', function () {
	Assert::exception(
		fn() => new Rectangle(-1, 20),
		InvalidArgumentException::class,
	);
});
```
- Named test blocks with labels
- Good for grouping related assertions
- Supports global `setUp()` and `tearDown()` functions
- Labels are printed during execution

**Style 3: TestCase classes** (`.phpt` files with `@testCase` annotation)
```php
<?php
/**
 * @testCase
 */
class MyTest extends Tester\TestCase
{
	protected function setUp() { /* ... */ }

	public function testSomething() { /* ... */ }

	/** @throws InvalidArgumentException */
	public function testException() { /* ... */ }

	protected function tearDown() { /* ... */ }
}

(new MyTest)->run();
```
- xUnit-style structure with setup/teardown
- Better for integration tests
- Each `test*` method runs as separate test
- Supports `@throws` and `@dataProvider` method annotations

### TestCase Method Discovery

When using `@testCase`:
1. **List Mode** - Runner calls test with `--method=nette-tester-list-methods` to discover all `test*` methods
2. **Execute Mode** - Runner calls test with `--method=testFoo` for each individual method
3. This two-phase approach enables efficient parallel execution of TestCase methods

### Data Provider Support

Data providers enable parameterized testing:

**INI format:**
```ini
[dataset1]
input = "test"
expected = "TEST"

[dataset2]
input = "foo"
expected = "FOO"
```

**PHP format:**
```php
return [
	'dataset1' => ['input' => 'test', 'expected' => 'TEST'],
	'dataset2' => ['input' => 'foo', 'expected' => 'FOO'],
];
```

**Query syntax for filtering:**
```php
/**
 * @dataProvider data.ini, >= 8.1
 */
```

## Test Annotations

Annotations control how tests are handled by the test runner. Written in PHPDoc at the beginning of test files. **Note:** Annotations are ignored when tests are run manually as PHP scripts.

### File-Level Annotations

**@skip** - Skip the test entirely
```php
/**
 * @skip Temporarily disabled
 */
```

**@phpVersion** - Skip if PHP version doesn't match
```php
/**
 * @phpVersion >= 8.1
 * @phpVersion < 8.4
 * @phpVersion != 8.2.5
 */
```

**@phpExtension** - Skip if required extensions not loaded
```php
/**
 * @phpExtension pdo, pdo_mysql
 * @phpExtension json
 */
```

**@dataProvider** - Run test multiple times with different data
```php
/**
 * @dataProvider databases.ini
 * @dataProvider? optional-file.ini    # Skip if file doesn't exist
 * @dataProvider data.ini, postgresql, >=9.0  # With filter condition
 */

// Access data in test
$args = Tester\Environment::loadData();
// Returns array with section data from INI/PHP file
```

**@multiple** - Run test N times
```php
/**
 * @multiple 10
 */
```

**@testCase** - Treat file as TestCase class (enables parallel method execution)
```php
/**
 * @testCase
 */
```

**@exitCode** - Expected exit code (default: 0)
```php
/**
 * @exitCode 56
 */
```

**@httpCode** - Expected HTTP code when running under CGI (default: 200)
```php
/**
 * @httpCode 500
 * @httpCode any    # Don't check HTTP code
 */
```

**@outputMatch** / **@outputMatchFile** - Verify test output matches pattern
```php
/**
 * @outputMatch %A%Fatal error%A%
 * @outputMatchFile expected-output.txt
 */
```

**@phpIni** - Set INI values for test
```php
/**
 * @phpIni precision=20
 * @phpIni memory_limit=256M
 */
```

### TestCase Method Annotations

**@throws** - Expect exception (alternative to Assert::exception)
```php
/**
 * @throws RuntimeException
 * @throws LogicException  Wrong argument order
 */
public function testMethod() { }
```

**@dataProvider** - Run method multiple times with different parameters
```php
// From method
/**
 * @dataProvider getLoopArgs
 */
public function testLoop($a, $b, $c) { }

public function getLoopArgs() {
	return [[1, 2, 3], [4, 5, 6]];
}

// From file
/**
 * @dataProvider loop-args.ini
 * @dataProvider loop-args.php
 */
public function testLoop($a, $b, $c) { }
```

## Assertions

### Core Assertion Methods

**Identity and equality:**
- `Assert::same($expected, $actual)` - Strict comparison (===)
- `Assert::notSame($expected, $actual)` - Strict inequality (!==)
- `Assert::equal($expected, $actual)` - Loose comparison (ignores object identity, array order)
- `Assert::notEqual($expected, $actual)` - Loose inequality

**Containment:**
- `Assert::contains($needle, array|string $haystack)` - Substring or array element
- `Assert::notContains($needle, array|string $haystack)` - Must not contain
- `Assert::hasKey(string|int $key, array $actual)` - Array must have key
- `Assert::notHasKey(string|int $key, array $actual)` - Array must not have key

**Boolean checks:**
- `Assert::true($value)` - Strict true (=== true)
- `Assert::false($value)` - Strict false (=== false)
- `Assert::truthy($value)` - Truthy value
- `Assert::falsey($value)` - Falsey value
- `Assert::null($value)` - Strict null (=== null)
- `Assert::notNull($value)` - Not null (!== null)

**Special values:**
- `Assert::nan($value)` - Must be NAN (use only this for NAN testing)
- `Assert::count($count, Countable|array $value)` - Element count

**Type checking:**
- `Assert::type(string|object $type, $value)` - Type validation
  - Supports: array, list, bool, callable, float, int, null, object, resource, scalar, string
  - Supports class names and instanceof checks

**Pattern matching:**
- `Assert::match($pattern, $actual)` - Regex or wildcard matching
- `Assert::notMatch($pattern, $actual)` - Must not match pattern
- `Assert::matchFile($file, $actual)` - Pattern loaded from file

**Exceptions and errors:**
- `Assert::exception(callable $fn, string $class, ?string $message, $code)` - Expect exception
- `Assert::error(callable $fn, int|string|array $type, ?string $message)` - Expect PHP error/warning
- `Assert::noError(callable $fn)` - Must not generate any error or exception

**Other:**
- `Assert::fail(string $message)` - Force test failure
- `Assert::with($object, callable $fn)` - Access private/protected members

### Expect Pattern for Complex Assertions

Use `Tester\Expect` inside `Assert::equal()` for complex structure validation:

```php
use Tester\Expect;

Assert::equal([
	'id' => Expect::type('int'),
	'username' => 'milo',
	'password' => Expect::match('%h%'),           // hex string
	'created_at' => Expect::type(DateTime::class),
	'items' => Expect::type('array')->andCount(5),
], $result);
```

**Available Expect methods:**
- `Expect::type($type)` - Type expectation
- `Expect::match($pattern)` - Pattern expectation
- `Expect::count($count)` - Count expectation
- `Expect::that(callable $fn)` - Custom validator
- Chain with `->andCount()`, etc.

### Failed Assertion Output

When assertions fail with complex structures, Tester saves dumps to `output/` directory:
```
tests/
├── output/
│   ├── MyTest.actual      # Actual value
│   └── MyTest.expected    # Expected value
└── MyTest.phpt            # Failing test
```

Change output directory: `Tester\Dumper::$dumpDir = __DIR__ . '/custom-output';`

## Helper Classes and Functions

### HttpAssert (version 2.5.6+)

Testing HTTP servers with fluent interface:

```php
use Tester\HttpAssert;

// Basic request
$response = HttpAssert::fetch('https://api.example.com/users');
$response
	->expectCode(200)
	->expectHeader('Content-Type', contains: 'json')
	->expectBody(contains: 'users');

// Custom request
HttpAssert::fetch(
	'https://api.example.com/users',
	method: 'POST',
	headers: [
		'Authorization' => 'Bearer token123',
		'Accept: application/json',    // String format also supported
	],
	cookies: ['session' => 'abc123'],
	follow: false,                     // Don't follow redirects
	body: '{"name": "John"}'
)
	->expectCode(201);

// Status code validation
$response
	->expectCode(200)                        // Exact code
	->expectCode(fn($code) => $code < 400)   // Custom validation
	->denyCode(404)                          // Must not be 404
	->denyCode(fn($code) => $code >= 500);   // Must not be server error

// Header validation
$response
	->expectHeader('Content-Type')                     // Header exists
	->expectHeader('Content-Type', 'application/json') // Exact value
	->expectHeader('Content-Type', contains: 'json')   // Contains text
	->expectHeader('Server', matches: 'nginx %a%')     // Matches pattern
	->denyHeader('X-Debug')                            // Must not exist
	->denyHeader('X-Debug', contains: 'error');        // Must not contain

// Body validation
$response
	->expectBody('OK')                            // Exact match
	->expectBody(contains: 'success')             // Contains text
	->expectBody(matches: '%A%hello%A%')          // Matches pattern
	->expectBody(fn($body) => json_decode($body)) // Custom validator
	->denyBody('Error')                           // Must not match
	->denyBody(contains: 'exception');            // Must not contain
```

### DomQuery

CSS selector-based HTML/XML querying (extends SimpleXMLElement):

```php
use Tester\DomQuery;

$dom = DomQuery::fromHtml('<article class="post">
	<h1>Title</h1>
	<div class="content">Text</div>
</article>');

// Check element existence
Assert::true($dom->has('article.post'));
Assert::true($dom->has('h1'));

// Find elements (returns array of DomQuery objects)
$headings = $dom->find('h1');
Assert::same('Title', (string) $headings[0]);

// Check if element matches selector
$content = $dom->find('.content')[0];
Assert::true($content->matches('div'));
Assert::false($content->matches('p'));

// Find closest ancestor
$article = $content->closest('.post');
Assert::true($article->matches('article'));
```

### FileMock

Emulate files in memory for testing file operations:

```php
use Tester\FileMock;

// Create virtual file
$file = FileMock::create('initial content');

// Use with file functions
file_put_contents($file, "Line 1\n", FILE_APPEND);
file_put_contents($file, "Line 2\n", FILE_APPEND);

// Verify content
Assert::same("initial contentLine 1\nLine 2\n", file_get_contents($file));

// Works with parse_ini_file, fopen, etc.
```

### Environment Helpers

**Environment::setup()** - Must be called in bootstrap
- Improves error dump readability with coloring
- Enables assertion tracking (tests without assertions fail)
- Starts code coverage collection (when --coverage used)
- Prints OK/FAILURE status at end

**Environment::setupFunctions()** - Creates global test functions
```php
// In bootstrap.php
Tester\Environment::setup();
Tester\Environment::setupFunctions();

// In tests
test('description', function () { /* ... */ });
setUp(function () { /* runs before each test() */ });
tearDown(function () { /* runs after each test() */ });
```

**Environment::skip($message)** - Skip test with reason
```php
if (!extension_loaded('redis')) {
	Tester\Environment::skip('Redis extension required');
}
```

**Environment::lock($name, $dir)** - Prevent parallel execution
```php
// For tests that need exclusive database access
Tester\Environment::lock('database', __DIR__ . '/tmp');
```

**Environment::bypassFinals()** - Remove final keywords during loading
```php
Tester\Environment::bypassFinals();

class MyTestClass extends NormallyFinalClass { }
```

**Environment variables:**
- `Environment::VariableRunner` - Detect if running under test runner
- `Environment::VariableThread` - Get thread number in parallel execution

### Helpers::purge($dir)

Create directory and delete all content (useful for temp directories):
```php
Tester\Helpers::purge(__DIR__ . '/temp');
```

## Code Coverage

### Multi-Engine Support

The framework supports three coverage engines (auto-detected):
- **PCOV** - Fastest, modern, recommended
- **Xdebug** - Most common, slower
- **PHPDBG** - Built into PHP, no extension needed

### Coverage Data Aggregation

Coverage collection uses file-based aggregation with locking for parallel test execution:
1. Each test process collects coverage data
2. At shutdown, writes to shared file (with `flock()`)
3. Merges with existing data using `array_replace_recursive()`
4. Distinguishes positive (executed) vs negative (not executed) lines

**Engine priority:** PCOV → PHPDBG → Xdebug

**Memory management for large tests:**
```php
// In tests that consume lots of memory
Tester\CodeCoverage\Collector::flush();
// Writes collected data to file and frees memory
// No effect if coverage not running or using Xdebug
```

**Generate coverage reports:**
```bash
# HTML report
src/tester tests --coverage coverage.html --coverage-src src

# Clover XML report (for CI)
src/tester tests --coverage coverage.xml --coverage-src src

# Multiple source paths
src/tester tests --coverage coverage.html \
  --coverage-src src \
  --coverage-src app
```

## Communication Patterns

### Environment Variables

Parent-child process communication uses environment variables:
- `NETTE_TESTER_RUNNER` - Indicates test is running under runner
- `NETTE_TESTER_THREAD` - Thread number for parallel execution
- `NETTE_TESTER_COVERAGE` - Coverage file path
- `NETTE_TESTER_COVERAGE_ENGINE` - Which coverage engine to use

### Pattern Matching

The `Assert::match()` method supports powerful pattern matching with wildcards and regex:

**Wildcard patterns:**
- `%a%` - One or more of anything except line ending characters
- `%a?%` - Zero or more of anything except line ending characters
- `%A%` - One or more of anything including line ending characters (multiline)
- `%A?%` - Zero or more of anything including line ending characters
- `%s%` - One or more whitespace characters except line ending
- `%s?%` - Zero or more whitespace characters except line ending
- `%S%` - One or more characters except whitespace
- `%S?%` - Zero or more characters except whitespace
- `%c%` - A single character of any sort (except line ending)
- `%d%` - One or more digits
- `%d?%` - Zero or more digits
- `%i%` - Signed integer value
- `%f%` - Floating-point number
- `%h%` - One or more hexadecimal digits
- `%w%` - One or more alphanumeric characters
- `%%` - Literal % character

**Regular expressions:**
Must be delimited with `~` or `#`:
```php
Assert::match('#^[0-9a-f]+$#i', $hexValue);
Assert::match('~Error in file .+ on line \d+~', $errorMessage);
```

**Examples:**
```php
// Wildcard patterns
Assert::match('%h%', 'a1b2c3');                    // Hex string
Assert::match('Error in file %a% on line %i%', $error);  // Dynamic parts
Assert::match('%A%hello%A%world%A%', $multiline);  // Multiline matching

// Regular expression
Assert::match('#^\d{4}-\d{2}-\d{2}$#', $date);    // Date format
```

## Coding Conventions

- All source files must include `declare(strict_types=1)`
- Use tabs for indentation
- Follow Nette Coding Standard (based on PSR-12)
- File extension `.phpt` for test files
- Place return type and opening brace on same line (PSR-12 style)
- Document PHPDoc annotations when they control test behavior

## File Organization

```
src/
├── Runner/              # Test execution orchestration
│   ├── Runner.php       # Main orchestrator
│   ├── Job.php          # Process wrapper
│   ├── Test.php         # Test state/data
│   ├── TestHandler.php  # Annotation processing
│   └── Output/          # Multiple output formats
├── Framework/           # Testing utilities
│   ├── Assert.php       # 25+ assertion methods
│   ├── TestCase.php     # xUnit-style base class
│   ├── Environment.php  # Test context setup
│   └── DataProvider.php # External test data
├── CodeCoverage/        # Coverage collection
│   ├── Collector.php    # Multi-engine collector
│   └── Generators/      # HTML & XML reports
├── bootstrap.php        # Framework initialization
├── tester.php           # CLI entry point (manual class loading)
└── tester               # Executable wrapper

tests/
├── bootstrap.php        # Test suite initialization
├── Framework/           # Framework layer tests
├── Runner/              # Runner layer tests
├── CodeCoverage/        # Coverage layer tests
└── RunnerOutput/        # Output format tests
```

## Key Design Principles

1. **Immutability** - `Test` class uses clone-and-modify pattern to prevent accidental state mutations
2. **Strategy Pattern** - `OutputHandler` interface enables multiple output formats simultaneously
3. **No Autoloading** - Manual class loading in `tester.php` ensures no autoloader conflicts
4. **Self-Testing** - The framework uses itself for testing (83 test files)
5. **Smart Result Caching** - Failed tests run first on next execution to speed up development workflow
   - Caches test results in temp directory
   - Uses MD5 hash of test signature for cache filename
   - Prioritizes previously failed tests for faster feedback

### Test Runner Behavior

**First run:**
```
src/tester tests
# Runs all tests in discovery order
```

**Subsequent runs:**
- Failed tests from previous run execute first
- Helps quickly verify if bugs are fixed
- Non-zero exit code if any test fails

**Parallel execution (default):**
- 8 threads by default
- Tests run in separate PHP processes
- Results aggregated as they complete
- Use `-j 1` for serial execution when debugging

**Watch mode:**
```bash
src/tester --watch src tests
# Auto-reruns tests when files change
# Great for TDD workflow
```

## Common Development Tasks

When adding new assertions to `Assert` class:
- Add method to `src/Framework/Assert.php`
- Add corresponding test to `tests/Framework/Assert.*.phpt`
- Document in readme.md assertion table

When modifying test execution flow:
- Consider impact on both simple tests and TestCase-based tests
- Test with both serial (`-j 1`) and parallel execution
- Verify annotation processing in `TestHandler`

When working with output formats:
- Implement `OutputHandler` interface
- Add tests in `tests/RunnerOutput/`
- Update CLI help text in `CliTester.php`

## Testing the Tester

The project uses itself for testing. The test bootstrap (`tests/bootstrap.php`) creates a `PhpInterpreter` that mirrors the current PHP environment.

**Important when running tests:**
- Tests run in isolated processes (like production usage)
- Coverage requires Xdebug/PCOV/PHPDBG
- Some tests verify specific output formats and patterns
- Test files use `.phptx` extension when they shouldn't be automatically discovered

## Important Notes and Edge Cases

### Tests Must Execute Assertions

A test without any assertion calls is considered **suspicious** and will fail:
```
Error: This test forgets to execute an assertion.
```

If a test intentionally has no assertions, explicitly mark it:
```php
Assert::true(true);  // Mark test as intentionally assertion-free
```

### Proper Test Termination

**Don't use exit() or die()** to signal test failure:
```php
// Wrong - exit code 0 signals success
exit('Error in connection');

// Correct - use Assert::fail()
Assert::fail('Error in connection');
```

### PHP INI Handling

Tester runs PHP processes with `-n` flag (no php.ini loaded):
- Ensures consistent test environment
- System extensions from `/etc/php/conf.d/*.ini` are NOT loaded
- Use `-C` flag to load system php.ini
- Use `-c path/to/php.ini` for custom php.ini
- Use `-d key=value` for individual INI settings

### Test File Naming

Test runner discovers tests by file pattern:
- `*.phpt` - Standard test files
- `*Test.php` - Alternative test file pattern
- `.phptx` extension - Tests that shouldn't be auto-discovered (used in Tester's own test suite)

### Bootstrap Pattern

Typical test bootstrap structure:
```php
// tests/bootstrap.php
require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();
Tester\Environment::setupFunctions();  // Optional, for test() function

date_default_timezone_set('Europe/Prague');
define('TempDir', __DIR__ . '/tmp/' . getmypid());
Tester\Helpers::purge(TempDir);
```

### Directory Structure for Tests

Organize tests by namespace:
```
tests/
├── NamespaceOne/
│   ├── MyClass.getUsers.phpt
│   ├── MyClass.setUsers.phpt
│   └── ...
├── NamespaceTwo/
│   └── ...
├── bootstrap.php
└── ...
```

Run tests from specific folder:
```bash
src/tester tests/NamespaceOne
```

### Unique Philosophy

**Each test is a runnable PHP script:**
- Can be executed directly: `php tests/MyTest.phpt`
- Can be debugged in IDE with breakpoints
- Can be opened in browser (for CGI tests)
- Makes test development fast and interactive

## CI/CD

GitHub Actions workflow tests across:
- 3 operating systems (Ubuntu, Windows, macOS)
- 6 PHP versions (8.0 - 8.5)
- 18 total combinations

This extensive matrix ensures compatibility across all supported environments.
