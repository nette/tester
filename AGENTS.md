# To My Agents!

It is my fervent wish that this file guide every AI coding agent working with code in this repository.

## Documentation

Any distilled, agent-facing documentation for this package - how it works
internally and the rationale behind key design decisions - lives in `docs/`.
Consult it before non-trivial changes; it is the source of truth from which the
public manual is distilled.

This repo is the *implementation* of Tester (not a guide to using it). It has two
seams - the in-process framework (assertions, `TestCase`, `Environment`) and the
out-of-process runner (orchestration, isolation, annotation dispatch) - bridged by
process exit codes and env vars. Read `docs/internals.md` before editing either.

## Project Overview

Nette Tester is a lightweight, zero-dependency PHP testing framework. Each test
runs in a **fully isolated PHP process**; tests run in parallel by default (8
threads), are annotation-driven, and the framework is **self-hosting** (it tests
itself).

- **PHP Version**: 8.0–8.5 (composer upper bound)
- **Package**: `nette/tester`

## Essential Commands

```bash
# Run the suite with the in-repo runner (not vendor/bin)
src/tester tests -s
src/tester tests/Framework/Assert.same.phpt -s

# A single test is a plain runnable script
php tests/Framework/Assert.same.phpt

# Static analysis
composer phpstan
```

Useful runner flags: `-j <n>` (parallel jobs; `-j 1` = serial for debugging),
`-o <format>` (console/console-lines/tap/junit/log/none), `-C`/`-c`/`-d` (php.ini),
`--watch`, `--stop-on-fail`, `--cider` (hidden, not in `--help`: live per-thread
panel; needs `-j` >= 2, otherwise falls back to console-lines).

## Conventions

- Every file starts with `declare(strict_types=1);`; **tabs**; Nette Coding Standard,
  but **PSR-12 same-line braces** here (return type and opening brace on the same
  line - unlike most Nette packages).
- Tests are `.phpt` (auto-discovered) or `*Test.php`; **`.phptx` is deliberately
  NOT auto-discovered** (used in Tester's own suite for fixtures). There is **no
  autoloading** - `tester.php` loads classes manually to avoid autoloader conflicts.

## Working in this repo

- **`AssertException` is deliberately catchable, and this is the single most
  important invariant.** `Assert::notEqual` catches it to invert an `Expect`
  comparison, `TestCase::runTest` catches it to decorate the failure message,
  `TestCase::run` and the `test()` helper catch (and rethrow) so a `TestCase`
  continues after one method fails and `test()` can print its × marker. **Never
  turn `Assert::fail()` into a hard `exit`.**
- **`Assert::$counter` increments at the top of every assertion, before the check**
  (it counts attempts). It backs the "this test forgets to execute an assertion"
  guard, which `setup()` disables for `@outputMatch`/`@outputMatchFile` tests.
  `fail()` throws during the test body but delegates to `$onFailure` once
  `Environment` installs it in a shutdown handler.
- **`match()` uses PCRE only when the pattern is delimited with `~` or `#`**
  (`isPcre`); otherwise it's a wildcard mask (`%a%`, `%A%`, `%d%`, `%h%`, ...). Keep
  the two branches distinct - the delimiter is the only signal.
- **The runner reads annotations statically (regex over the source) before spawning
  the process,** then dispatches by reflection to `initiate*`/`assess*` methods.
  Consequences: file-level annotations **cannot** be replaced by PHP attributes, and
  adding a new annotation means adding a method. `@testCase` fans out in two phases
  (list `test*` methods, then one job per method); the method list is cached in
  temp and invalidated by mtimes of the class + parents + traits files.
- **`FileMutator` (`bypassFinals`) registers itself as the `file://` stream
  wrapper;** its `native()` must restore the original wrapper and re-register itself
  in a `finally`, or every internal fs call recurses. Preserve that dance.
- **`Environment::print()` bypasses the ANSI-stripping `ob_start` buffer** (writes
  straight to STDOUT), which is why status/fatal lines always appear.
- **The CLI help text IS the option grammar.** `CommandLine` parses option names,
  arguments, enums and defaults out of the usage string (`(default: 8)` is where
  `-j`'s default lives), so rewording `--help` can change parsing; hidden options
  (`--debug`, `--cider`, ...) exist only in the `$defaults` array.
- Jobs are ordered by the previous run's result (cache in temp): new tests first,
  then failed, passed, and skipped last.
- User-facing how-to (writing tests, the assertion/annotation catalog, HttpAssert,
  DomQuery, FileMock, coverage usage) is manual material and lives in the web docs.
