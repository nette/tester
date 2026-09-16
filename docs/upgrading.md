# Upgrading nette/tester

## 2.6.2

- **Signature** `Tester\Assert::throws()`: the `$code` parameter is now typed `int|string|null`; passing another type throws `TypeError` (e9d9cd13)
- **Changed** `Tester\Assert::exception()` now checks the message pattern even when it is `''` or `'0'`; previously such a value disabled the check, pass `null` to disable it (f868804a)
- **Changed** `Tester\Assert::error()` now checks the message pattern even when it is `''` or `'0'`; previously such a value disabled the check, pass `null` to disable it (f868804a)
- **Changed** `Tester\Helpers::purge()` now also refuses a path that merely resolves to a root directory, for example one ending with `..` or a symlink to the root; previously only the literal form of the path was checked (2f8804d9)
- **Changed** `Tester\CodeCoverage\Generators\CloverXMLGenerator` now counts free functions among methods and all executable lines of a loaded file among statements; previously only classes and traits were summed, so the reported file and project metrics including the overall coverage percentage change (69b2b69e)
- **Changed** `Tester\Runner\Output\JUnitPrinter` now reports failed tests in the `failures` attribute of `<testsuite>` and writes `errors="0"`; previously failed tests were counted in `errors` and `failures` was missing; affects consumers reading only `errors` (279449d7)
- **Changed** `Tester\TestCase::run()` now runs all test methods even after a failure, prints the results first and the failure details after them, and rethrows only the last error; previously the first failed or errored method aborted the run (00fee261)
- **Changed** `Tester\HttpAssert::fetch()` with `follow: true` now takes the headers of the final response only and combines a header repeated within one response into a single comma-separated value; previously the headers of the whole redirect chain were merged and only the last occurrence of a repeated header was kept (0c460688)
- **Changed** `Tester\Environment::setupErrors()` now sets `zend.exception_ignore_args=0`, so stack traces always include call arguments regardless of the php.ini profile (fd75d717)
- **Changed** the `@multiple` annotation now requires a positive integer; a value of `0` or a non-numeric value fails the test, previously such a value silently ran the test twice (83076e67)
- **Changed** the `@phpVersion` annotation now skips the test unless the interpreter satisfies the condition; previously the operands of the comparison were swapped, so `@phpVersion 8.4.3` was skipped on PHP 8.4.3 and `@phpVersion < 8.0` ran on PHP 8.0 (53e42764)
- **Changed** the `tester` command no longer prints the final "% covered" line to stdout with `-o tap`, `-o junit` or `-o none`, and exits with code 1 when the requested coverage report cannot be generated; previously it exited with 0 (7f08c24f)

## 2.6.0

- **Signature** `Tester\Assert::exception()`: the `$code` parameter is now typed `int|string|null`; passing another type throws `TypeError` (cbcdf449)
- **Signature** `testException()`: the `$code` parameter is now typed `int|string|null`; passing another type throws `TypeError` (cbcdf449)
- **Changed** `testException()` now runs the registered `setUp()` and `tearDown()` callbacks because it calls `test()` internally; previously it ran neither (6424da48)
- **Changed** `test()` now calls `tearDown()` even when the test fails; previously a failing test skipped it (86e87c9f)
- **Changed** the `tester` command now uses the system-wide php.ini by default; `-c <path>` now means "use this php.ini and ignore the system configuration" and only `-c` together with `-C` includes it; previously no php.ini was used unless `-C` was given, and `-c` looked for a php.ini file or directory next to the given path (a4037fab)

## 2.5.5

- **Changed** `Tester\DomQuery::fromHtml()` now passes the input through `mb_convert_encoding($html, 'HTML', 'UTF-8')` before parsing, which changes how non-ASCII characters and entities in the input are handled (32b489eb)

## 2.5.4

- **Changed** an INI file used as a `@dataProvider` is now parsed with `INI_SCANNER_TYPED`, so values such as `1`, `true` or `null` arrive as `int`, `bool` and `null`; previously every value was a string (de1e470b)

## 2.5.3

- **Changed** `test()` now throws an `Exception` when called with more than two arguments; previously the extra arguments were silently ignored (309dc195)

## 2.5.2

- **Changed** `Tester\DomQuery::find()` now searches only the descendants of the current node; previously it always searched from the document root regardless of the current node (1271f4c5)
- **Changed** `Tester\DomQuery::has()` now searches only the descendants of the current node, because it calls `Tester\DomQuery::find()`; previously it always searched from the document root (1271f4c5)

## 2.5.0

- **Removed** `Tester\Runner\Job::RUN_ASYNC`: pass `async: true` to `Tester\Runner\Job::run()` (fa7daccf)
- **Replaced** `Tester\Runner\Job::RUN_COLLECT_ERRORS` with `Tester\Runner\Job::setTempDirectory()`: call it with a writable directory before `Tester\Runner\Job::run()`; stderr is then captured into a temporary file and exposed via `Tester\Runner\Test::$stderr` and `Tester\Runner\Test::getOutput()` (593cf600)
- **Renamed** `Tester\CodeCoverage\Collector::ENGINE_PCOV` → `Tester\CodeCoverage\Collector::EnginePcov`; the old name was removed (9942c285)
- **Renamed** `Tester\CodeCoverage\Collector::ENGINE_PHPDBG` → `Tester\CodeCoverage\Collector::EnginePhpdbg`; the old name was removed (9942c285)
- **Renamed** `Tester\CodeCoverage\Collector::ENGINE_XDEBUG` → `Tester\CodeCoverage\Collector::EngineXdebug`; the old name was removed (9942c285)
- **Renamed** `Tester\CodeCoverage\Generators\AbstractGenerator::CODE_TESTED` → `Tester\CodeCoverage\Generators\AbstractGenerator::LineTested`; the old name was removed; affects subclasses using it (9942c285)
- **Renamed** `Tester\CodeCoverage\Generators\AbstractGenerator::CODE_UNTESTED` → `Tester\CodeCoverage\Generators\AbstractGenerator::LineUntested`; the old name was removed; affects subclasses using it (9942c285)
- **Renamed** `Tester\CodeCoverage\Generators\AbstractGenerator::CODE_DEAD` → `Tester\CodeCoverage\Generators\AbstractGenerator::LineDead`; the old name was removed; affects subclasses using it (9942c285)
- **Renamed** `Tester\Runner\CommandLine::Realpath` → `Tester\Runner\CommandLine::RealPath`; the old name was removed (9942c285)
- **Renamed** `Tester\Runner\Job::CODE_NONE` → `Tester\Runner\Job::CodeNone`; the old name was removed (9942c285)
- **Renamed** `Tester\Runner\Job::CODE_OK` → `Tester\Runner\Job::CodeOk`; the old name was removed (9942c285)
- **Renamed** `Tester\Runner\Job::CODE_SKIP` → `Tester\Runner\Job::CodeSkip`; the old name was removed (9942c285)
- **Renamed** `Tester\Runner\Job::CODE_FAIL` → `Tester\Runner\Job::CodeFail`; the old name was removed (9942c285)
- **Renamed** `Tester\Runner\Job::CODE_ERROR` → `Tester\Runner\Job::CodeError`; the old name was removed (9942c285)
- **Renamed** `Tester\Runner\Job::RUN_USLEEP` → `Tester\Runner\Job::RunSleep`; the old name was removed (9942c285)
- **Renamed** `Tester\Environment::COLORS` → `Tester\Environment::VariableColors`; the old name is silently deprecated (9942c285)
- **Renamed** `Tester\Environment::COVERAGE` → `Tester\Environment::VariableCoverage`; the old name is silently deprecated (9942c285)
- **Renamed** `Tester\Environment::COVERAGE_ENGINE` → `Tester\Environment::VariableCoverageEngine`; the old name is silently deprecated (9942c285)
- **Renamed** `Tester\Environment::RUNNER` → `Tester\Environment::VariableRunner`; the old name is silently deprecated (9942c285)
- **Renamed** `Tester\Environment::THREAD` → `Tester\Environment::VariableThread`; the old name is silently deprecated (9942c285)
- **Renamed** `Tester\Runner\Test::PREPARED` → `Tester\Runner\Test::Prepared`; the old name is silently deprecated (9942c285)
- **Renamed** `Tester\Runner\Test::FAILED` → `Tester\Runner\Test::Failed`; the old name is silently deprecated (9942c285)
- **Renamed** `Tester\Runner\Test::PASSED` → `Tester\Runner\Test::Passed`; the old name is silently deprecated (9942c285)
- **Renamed** `Tester\Runner\Test::SKIPPED` → `Tester\Runner\Test::Skipped`; the old name is silently deprecated (9942c285)
- **Signature** `Tester\Assert::contains()`: `$actual` is now typed `array|string`; another type throws `TypeError` instead of failing with `Tester\AssertException` (8cbcd049)
- **Signature** `Tester\Assert::notContains()`: `$actual` is now typed `array|string`; another type throws `TypeError` instead of failing with `Tester\AssertException` (8cbcd049)
- **Signature** `Tester\Assert::count()`: `$value` is now typed `array|Countable`; another type throws `TypeError` instead of failing with `Tester\AssertException` (8cbcd049)
- **Signature** `Tester\Assert::error()`: `$expectedType` is now typed `int|string|array`; another type throws `TypeError`, previously it was handled silently (8cbcd049)
- **Signature** `Tester\Assert::hasKey()`: `$key` is now typed `string|int`; another type throws `TypeError` instead of failing with `Tester\AssertException` (8cbcd049)
- **Signature** `Tester\Assert::hasNotKey()`: `$key` is now typed `string|int`; another type throws `TypeError` instead of failing with `Tester\AssertException` (8cbcd049)
- **Signature** `Tester\Assert::match()`: `$actual` is now typed `string`; another scalar throws `TypeError` instead of failing with `Tester\AssertException` (8cbcd049)
- **Signature** `Tester\Assert::matchFile()`: `$actual` is now typed `string`; another scalar throws `TypeError` instead of failing with `Tester\AssertException` (8cbcd049)
- **Signature** `Tester\Assert::type()`: `$type` is now typed `string|object`; another type throws `TypeError` instead of a plain `Exception` (8cbcd049)
- **Signature** `Tester\Assert::with()`: `$objectOrClass` is now typed `object|string`; another type throws `TypeError`, previously it was accepted with unpredictable results (8cbcd049)
- **Signature** native property and return types were added throughout the package, so values that used to be coerced or left null are now rejected; among the affected symbols are `Tester\Assert::$expandPatterns`, `Tester\Assert::$counter`, `Tester\Dumper::$maxLength`, `Tester\Dumper::$maxDepth`, `Tester\Dumper::$maxPathSegments`, `Tester\Dumper::$dumpDir`, `Tester\Environment::$useColors`, `Tester\Environment::$checkAssertions`, `Tester\Runner\Runner::$threadCount`, `Tester\Runner\Runner::$stopOnFail`, `Tester\Runner\Runner::$outputHandlers`, `Tester\Runner\Runner::$paths`, `Tester\Runner\Test::$message`, `Tester\Runner\Test::$title`, `Tester\Runner\Test::$stdout` and `Tester\CodeCoverage\Generators\AbstractGenerator::$sources` (8cbcd049)
- **Changed** `Tester\Dumper::toLine()` now renders booleans and null in lowercase as `true`, `false` and `null`; previously `TRUE`, `FALSE` and `NULL`; assertion failure messages and any `.expected` file containing the old form must be updated (marked @internal) (aa8e66fd)
- **Changed** `Tester\Environment` now writes the final failure, skip and OK message directly to `STDOUT` with `fwrite()`, bypassing PHP output buffers; previously the text was echoed after flushing open buffers, so a test capturing it with its own `ob_start()` no longer sees it (50282f0d)

## 2.4.2

- **Renamed** `Tester\Runner\CommandLine::ARGUMENT` → `Tester\Runner\CommandLine::Argument`; the old name was removed (5774dceb)
- **Renamed** `Tester\Runner\CommandLine::OPTIONAL` → `Tester\Runner\CommandLine::Optional`; the old name was removed (5774dceb)
- **Renamed** `Tester\Runner\CommandLine::REPEATABLE` → `Tester\Runner\CommandLine::Repeatable`; the old name was removed (5774dceb)
- **Renamed** `Tester\Runner\CommandLine::ENUM` → `Tester\Runner\CommandLine::Enum`; the old name was removed (5774dceb)
- **Renamed** `Tester\Runner\CommandLine::REALPATH` → `Tester\Runner\CommandLine::Realpath`; the old name was removed (5774dceb)
- **Renamed** `Tester\Runner\CommandLine::NORMALIZER` → `Tester\Runner\CommandLine::Normalizer`; the old name was removed (5774dceb)
- **Renamed** `Tester\Runner\CommandLine::VALUE` → `Tester\Runner\CommandLine::Value`; the old name was removed (5774dceb)
- **Changed** `Tester\Runner\Runner::run()` now throws `Tester\Runner\InterruptException` when the run is interrupted by Ctrl+C; previously the run loop ended quietly without an exception (6118f861)
- **Changed** `Tester\Environment::setupColors()` now honours `FORCE_COLOR` and detects a terminal only through `sapi_windows_vt100_support()` or `stream_isatty()`; `ConEmuANSI`, `ANSICON` and `term=xterm` no longer enable colors on Windows (234e8042)

## 2.4.1

- **Changed** a `@dataProvider` method must now return arrays only; an item that is not an array throws `Tester\TestCaseException`, previously it passed unchecked (a496e855)

## 2.4.0

- **Renamed** `-l | --log <path>` → `-o log:<path>`; the old name still works as an alias (7dc3282c)
- **Changed** `Tester\DataProvider::load()` now returns an empty array when no record matches the query; previously it threw an `Exception`, so a `@dataProvider` with a query that matches nothing no longer fails (marked @internal) (1f4bce20)
- **Changed** `Tester\DataProvider::load()` now filters records with integer keys by the query like any other record; previously they were always kept (marked @internal) (1f4bce20)

## 2.3.5

- **Changed** `Tester\Environment::loadData()` now takes the `--dataprovider` argument as an exact dataset key; previously it was applied to the datasets as a query (217c8593)
- **Changed** `Tester\Environment::loadData()` now throws an `Exception` when the data provider returns no dataset for the query; previously it failed with a `TypeError` (217c8593)
- **Changed** `Tester\Assert::contains()` now fails with `Tester\AssertException` when `$needle` is not a string and `$actual` is; previously a `TypeError` was thrown (4c610ba3)
- **Changed** `Tester\Assert::notContains()` now fails with `Tester\AssertException` when `$needle` is not a string and `$actual` is; previously a `TypeError` was thrown (4c610ba3)

## 2.3.3

- **Changed** `Tester\DomQuery` is no longer declared when the libxml or SimpleXML extension is missing, so using the class throws `LogicException`; previously it failed later while parsing or using it (666cb04f)
- **Changed** `Tester\Environment::setupColors()` now honours `NO_COLOR` and detects a terminal through `sapi_windows_vt100_support()` on Windows and `stream_isatty()` elsewhere; `TERM=xterm-256color` alone no longer enables colors (8f238c76)

## 2.3.2

- **Changed** `Tester\Runner\Runner::run()` now skips directories named `vendor` when looking for tests; the list of skipped names is in `Tester\Runner\Runner::$ignoreDirs` (329d29e7)
- **Changed** `Tester\DomQuery::fromHtml()` no longer raises `E_USER_WARNING` for an unknown or invalid tag, so SVG, MathML and custom elements now pass without a warning (0b33a936)

## 2.2.0

- **Signature** `Tester\CodeCoverage\Collector::start()`: a required `string $engine` parameter was added (5787f670)
- **Changed** `Tester\Assert::noError()` now throws an `Exception` when called with more than one argument; previously the extra arguments were silently ignored (2e5da842)
- **Changed** `Tester\Helpers::purge()` now throws `InvalidArgumentException` when called with an empty string or a root path; previously it purged such a path (359ddb1a)
