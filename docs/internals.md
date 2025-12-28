# Tester internals

How `nette/tester` works underneath, for agents editing it. Two seams —
the **in-process framework** (assertions, `TestCase`, `Environment`) and the
**out-of-process runner** (orchestration, isolation, annotation dispatch) — bridged
by a narrow contract of **process exit codes and environment variables**. One file;
the seams cross-reference through that bridge.

## Assertion model: soft-by-default, exception-based, and deliberately catchable

- **`Assert::$counter` is incremented at the top of every assertion, before the
  actual check.** It backs the "this test forgets to execute an assertion" guard:
  `Environment`'s shutdown handler fails a test where `$checkAssertions` is on and
  `Assert::$counter` is still 0. So the counter counts *attempts*, not successes.
- **`Assert::fail()` either throws or delegates, depending on `$onFailure`.** With
  no handler it throws `AssertException`; with a handler set it **calls the handler
  instead of throwing**. The switch flips at a specific moment: `Environment`
  installs `Assert::$onFailure = handleException` **inside a shutdown function**, so
  during the test body `$onFailure` is null (assertions throw normally), and only
  at shutdown does a late assertion report via `handleException` rather than
  throwing into a dying process.
- **`AssertException` is intentionally catchable, and internal code depends on
  that.** The catch sites: `Assert::notEqual` swallows it to invert an `equal()`
  comparison (which may run `Expect` constraints); `TestCase::runTest` catches it
  to decorate the message with method, arguments and dataset, then rethrows;
  `TestCase::run` catches per-method `Throwable` so the remaining `test*` methods
  still run (it prints a √/×/s line per method and rethrows the last failure at
  the end); the `test()` helper catches to print its × marker before rethrowing.
  Note `Expect` itself does **not** collect failures — its first failing
  constraint throws. **Turning `fail()` into a hard `exit` would silently break
  all of this.** This is the single most important invariant here.
- **The forgot-assert guard is disabled for output-match tests.** `setup()` sets
  `$checkAssertions = !@outputMatch && !@outputMatchFile`, because those tests
  verify behavior through captured output, not assertion calls. `skip()` and
  `handleException` also clear it.

## `match()` has two grammars, chosen by a delimiter heuristic

`Assert::match`/`isMatching` treats the pattern as a **PCRE regex** iff `isPcre()`
matches — i.e. it **starts and ends with `~` or `#`** (plus optional flags).
Otherwise it is a **wildcard mask**: `%a%`, `%A%`, `%d%`, `%h%`, … are translated to
regex fragments and everything else is `preg_quote`d. The trap is the ambiguity: a
literal mask that happens to be wrapped in `#`/`~` is misread as a regex. When
editing pattern handling, keep the mask and regex branches distinct and remember
the delimiter is the only signal.

## The runner reads annotations statically, then dispatches by reflection

Test metadata is parsed from the file's **first docblock without executing the
file** (`TestHandler::getAnnotations`, regex over the source). This is a hard
constraint, not an implementation detail: the runner must decide skips and variants
*before* spawning the process.

Dispatch is **convention + reflection**, not a switch:

- `initiate()` scans its own `initiate<Name>` methods (via `get_class_methods` +
  a name match against annotation keys) and runs each. A method returns **`null`**
  (keep the test), a **`Test`** (replace it — possibly already carrying a skip
  result), or an **array of `Test`s** (variants). This is how `@dataProvider`,
  `@multiple`, and `@testCase` **fan one file out into many tests** before
  execution. One outlier: `initiatePhpIni` returns nothing and instead takes the
  interpreter **by reference** (`PhpInterpreter &$interpreter`), mutating it for
  all jobs of that file — don't copy that signature blindly when adding a new
  `initiate*` method.
- `assess(Job)` similarly dispatches `assess<Name>` methods (`@exitCode`,
  `@httpCode`, `@outputMatch`, `@outputMatchFile`) against the finished job; the
  first one that returns a failing `Test` wins, else the test passes. Defaults
  `exitcode=0`, `httpcode=200` are injected.

Two consequences worth knowing: **file-level annotations cannot be fully replaced
by PHP attributes** (a `.phpt` with no class gives an attribute nothing to attach
to, and the runner needs the metadata without loading the file); and adding a new
annotation means adding an `initiate*` or `assess*` method — nothing registers it
explicitly.

**Process isolation:** every test runs in its own PHP process (`Job` via
`proc_open`). A `@testCase` file is handled in **two phases** — the runner first
asks it to list `test*` methods (`--method=nette-tester-list-methods`, the
`TestCase::ListMethods` sentinel), then schedules each method as a **separate**
job (`--method=testFoo`). Phase 1 is **cached** in the temp dir
(`TestHandler.testCase.<md5>.list`): the listing child also prints `Dependency:`
lines (the class file plus all parents and traits), and the cache is invalidated
when any of those files' mtimes change — edit `sendMethodList` and
`initiateTestCase` together.

**Scheduling:** with a temp dir, each finished test's result is persisted to a
`<name>.<hash>.result` file, and the next run sorts jobs ascending by that value
(`Test::Prepared`=0, `Failed`=1, `Passed`=2, `Skipped`=3) — so new tests run
first, then last run's failures, and previously skipped tests go last.

**Child I/O:** with a temp dir, the child's **stderr goes to a file, not a pipe**
(`Job.pid-*.stderr`, read and deleted on finish). Stdout handling is
platform-forked for a reason: on Windows < PHP 8.5 the runner must keep reading
the pipe with blocking `stream_get_contents`, otherwise a child producing more
than the ~64 KB pipe buffer **deadlocks**; PHP 8.5+ on Windows has a fixed
`stream_select` and uses the non-blocking path like Linux/macOS
(`Job::isRunning`/`waitForActivity`). Keep the deadlock rationale in mind before
"simplifying" that branch.

## The runner↔child bridge: exit codes and environment variables

The child reports its outcome to the runner almost entirely through its **process
exit code** (`Job::CodeOk/CodeFail/CodeError/CodeSkip`): `handleException` exits
`CodeFail` for an `AssertException` and `CodeError` for anything else; `skip()`
exits `CodeSkip`; `assessExitCode` compares against `@exitCode`. Configuration flows
the other way through env vars — `NETTE_TESTER_RUNNER`, `NETTE_TESTER_THREAD`,
`NETTE_TESTER_COVERAGE(_ENGINE)`, and **`NETTE_TESTER_COLORS`**. That last one is the
runner→child color channel; an explicit color override in a bootstrap can therefore
disagree with the runner's decision (e.g. under `-o tap` / `--colors 0`).

## The CLI option grammar IS the help text

`CommandLine` does not have a separate option table: it **parses the usage string
passed to its constructor** (the one `CliTester::loadOptions` prints for
`--help`). From that text it derives, per option: whether it takes an argument
(`<...>` required, `[...]` optional), repeatability (a `...` suffix), allowed
enum values (`<a|b|c>`), and the default (a literal `(default: 8)` in the
description). So **rewording the help text can change parsing behavior** — e.g.
`-j`'s default of 8 lives only in that string. The second constructor argument
(`$defaults`) adds normalizers/realpath flags to parsed options and defines
**hidden options that are deliberately absent from the help** (`--debug`,
`--cider`, `--coverage-src`, `paths`). Editing help wording and editing option
behavior are the same act; treat the string as code.

## Coverage: every child merges into one shared file

Each test process (child, not runner) collects its own coverage and at shutdown
**merges into the single shared file** under `flock(LOCK_EX)`:
`array_replace_recursive($negative, $original, $positive)` — argument order is
load-bearing, it makes "covered" (positive) win over any earlier "uncovered"
(negative) data across processes. Two subtleties: the `save` handler is
registered as a **shutdown function inside a shutdown function**, so it runs
after the test's own shutdown handlers; and the runner pre-creates the file
empty, so a zero-size file after the run means tests never called
`Environment::setup()`.

## Output: a stripping buffer plus a buffer-bypassing `print`

`setupColors` decides colors (from `NETTE_TESTER_COLORS` if set, else tty/`NO_COLOR`/
`FORCE_COLOR` autodetection) and then **wraps all output in an `ob_start` handler
that strips ANSI when colors are off** — so ordinary `echo` is filtered centrally.
`Environment::print()` deliberately **bypasses that buffer**, writing straight to
`STDOUT` (with its own strip), which is why status lines and the fatal-error notice
appear even when the buffer is in an odd state.

On the runner side, `ConsolePrinter` has three modes: **dots** (default), **lines**
(`-o console-lines`), and **cider** (hidden `--cider` flag, not in `--help`).

## FileMutator: a `file://` stream wrapper, and the re-entrancy dance

`FileMutator` powers `bypassFinals()` by **registering itself as the `file` stream
wrapper**. On `stream_open` of a **`.php` file in `rb` mode** (i.e. an include) it
reads the real source, runs it through all registered mutators (`bypassFinals`
tokenizes and drops `T_FINAL`), writes the result to a `tmpfile`, and serves that;
every other path passes through untouched.

The critical, easy-to-miss part is **`native()`**: before calling any real
filesystem function it **restores the original wrapper**, and in a `finally`
**re-registers itself**. Without this, each internal `fopen`/`stat`/etc. would
recurse back into the wrapper. Preserve that restore/`finally` re-register exactly
when touching this class (it is `@internal` and excluded from PHPStan).

## Navigation map

| Concern | Where |
|---|---|
| Assert counter, soft/throw, catchability | `Assert::$counter`/`fail`, `Environment::setupErrors` (`$onFailure`) |
| Mask vs regex | `Assert::isMatching`/`isPcre` |
| Static annotations, variant/skip fan-out | `TestHandler::initiate`/`assess`, `Helpers::parseDocComment` |
| Process isolation, TestCase two-phase + cache | `Runner\Job`, `TestHandler::initiateTestCase`, `TestCase::sendMethodList` |
| Job ordering by last result | `Runner::run`/`getLastResult` |
| CLI grammar parsed from help text, hidden options | `Runner\CommandLine::__construct`, `CliTester::loadOptions` |
| Child stderr file, Windows pipe deadlock | `Job::setTempDirectory`/`isRunning` |
| Coverage merge semantics | `CodeCoverage\Collector::save` |
| Exit-code + env-var bridge | `Environment::handleException`/`exit`, `Job::Code*` (Skip=177, Fail=178, Error=255) |
| Color decision & output channels | `Environment::setupColors`/`print` |
| Final-stripping loader | `FileMutator` (`stream_open`, `native`), `Environment::bypassFinals` |
