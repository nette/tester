<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester;


/**
 * Assertion test helpers.
 */
class Assert
{
	/** used by equal() for comparing floats */
	private const EPSILON = 1e-10;

	/** used by match(); in values, each $ followed by number is backreference */
	public static $patterns = [
		'%%' => '%',            // one % character
		'%a%' => '[^\r\n]+',    // one or more of anything except the end of line characters
		'%a\?%' => '[^\r\n]*',  // zero or more of anything except the end of line characters
		'%A%' => '.+',          // one or more of anything including the end of line characters
		'%A\?%' => '.*',        // zero or more of anything including the end of line characters
		'%s%' => '[\t ]+',      // one or more white space characters except the end of line characters
		'%s\?%' => '[\t ]*',    // zero or more white space characters except the end of line characters
		'%S%' => '\S+',         // one or more of characters except the white space
		'%S\?%' => '\S*',       // zero or more of characters except the white space
		'%c%' => '[^\r\n]',     // a single character of any sort (except the end of line)
		'%d%' => '[0-9]+',      // one or more digits
		'%d\?%' => '[0-9]*',    // zero or more digits
		'%i%' => '[+-]?[0-9]+', // signed integer value
		'%f%' => '[+-]?\.?\d+\.?\d*(?:[Ee][+-]?\d+)?', // floating point number
		'%h%' => '[0-9a-fA-F]+', // one or more HEX digits
		'%w%' => '[0-9a-zA-Z_]+', //one or more alphanumeric characters
		'%ds%' => '[\\\\/]',    // directory separator
		'%(\[.+\][+*?{},\d]*)%' => '$1', // range
	];

	/** @var bool expand patterns in match() and matchFile() */
	public static $expandPatterns = true;

	/** @var callable  function (AssertException $exception): void */
	public static $onFailure;

	/** @var int  the count of assertions */
	public static $counter = 0;


	/**
	 * Asserts that two values are equal and have the same type and identity of objects.
	 */
	public static function same($expected, $actual, string $description = null): void
	{
		self::$counter++;
		if ($actual !== $expected) {
			self::fail(self::describe('%1 should be %2', $description), $actual, $expected);
		}
	}


	/**
	 * Asserts that two values are not equal or do not have the same type and identity of objects.
	 */
	public static function notSame($expected, $actual, string $description = null): void
	{
		self::$counter++;
		if ($actual === $expected) {
			self::fail(self::describe('%1 should not be %2', $description), $actual, $expected);
		}
	}


	/**
	 * Asserts that two values are equal and checks expectations. The identity of objects,
	 * the order of keys in the arrays and marginally different floats are ignored.
	 */
	public static function equal($expected, $actual, string $description = null): void
	{
		self::$counter++;
		if (!self::isEqual($expected, $actual)) {
			self::fail(self::describe('%1 should be equal to %2', $description), $actual, $expected);
		}
	}


	/**
	 * Asserts that two values are not equal and checks expectations. The identity of objects,
	 * the order of keys in the arrays and marginally different floats are ignored.
	 */
	public static function notEqual($expected, $actual, string $description = null): void
	{
		self::$counter++;
		try {
			$res = self::isEqual($expected, $actual);
		} catch (AssertException $e) {
		}

		if (empty($e) && $res) {
			self::fail(self::describe('%1 should not be equal to %2', $description), $actual, $expected);
		}
	}


	/**
	 * Asserts that a haystack (string or array) contains an expected needle.
	 * @param  mixed  $needle
	 * @param  array|string  $actual
	 */
	public static function contains($needle, $actual, string $description = null): void
	{
		self::$counter++;
		if (is_array($actual)) {
			if (!in_array($needle, $actual, true)) {
				self::fail(self::describe('%1 should contain %2', $description), $actual, $needle);
			}
		} elseif (is_string($actual)) {
			if (!is_string($needle)) {
				self::fail(self::describe('Needle %1 should be string'), $needle);

			} elseif ($needle !== '' && strpos($actual, $needle) === false) {
				self::fail(self::describe('%1 should contain %2', $description), $actual, $needle);
			}
		} else {
			self::fail(self::describe('%1 should be string or array', $description), $actual);
		}
	}


	/**
	 * Asserts that a haystack (string or array) does not contain an expected needle.
	 * @param  mixed  $needle
	 * @param  array|string  $actual
	 */
	public static function notContains($needle, $actual, string $description = null): void
	{
		self::$counter++;
		if (is_array($actual)) {
			if (in_array($needle, $actual, true)) {
				self::fail(self::describe('%1 should not contain %2', $description), $actual, $needle);
			}
		} elseif (is_string($actual)) {
			if (!is_string($needle)) {
				self::fail(self::describe('Needle %1 should be string'), $needle);

			} elseif ($needle === '' || strpos($actual, $needle) !== false) {
				self::fail(self::describe('%1 should not contain %2', $description), $actual, $needle);
			}
		} else {
			self::fail(self::describe('%1 should be string or array', $description), $actual);
		}
	}


	/**
	 * Asserts that a haystack has an expected key.
	 * @param  string|int  $key
	 */
	public static function hasKey($key, array $actual, string $description = null): void
	{
		self::$counter++;
		if (!is_int($key) && !is_string($key)) {
			self::fail(self::describe('Key %1 should be string or integer'), $key);

		} elseif (!array_key_exists($key, $actual)) {
			self::fail(self::describe('%1 should contain key %2', $description), $actual, $key);
		}
	}


	/**
	 * Asserts that a haystack doesn't have an expected key.
	 * @param  string|int  $key
	 */
	public static function hasNotKey($key, array $actual, string $description = null): void
	{
		self::$counter++;
		if (!is_int($key) && !is_string($key)) {
			self::fail(self::describe('Key %1 should be string or integer'), $key);

		} elseif (array_key_exists($key, $actual)) {
			self::fail(self::describe('%1 should not contain key %2', $description), $actual, $key);
		}
	}


	/**
	 * Asserts that a value is true.
	 * @param  mixed  $actual
	 */
	public static function true($actual, string $description = null): void
	{
		self::$counter++;
		if ($actual !== true) {
			self::fail(self::describe('%1 should be TRUE', $description), $actual);
		}
	}


	/**
	 * Asserts that a value is false.
	 * @param  mixed  $actual
	 */
	public static function false($actual, string $description = null): void
	{
		self::$counter++;
		if ($actual !== false) {
			self::fail(self::describe('%1 should be FALSE', $description), $actual);
		}
	}


	/**
	 * Asserts that a value is null.
	 * @param  mixed  $actual
	 */
	public static function null($actual, string $description = null): void
	{
		self::$counter++;
		if ($actual !== null) {
			self::fail(self::describe('%1 should be NULL', $description), $actual);
		}
	}


	/**
	 * Asserts that a value is not null.
	 * @param  mixed  $actual
	 */
	public static function notNull($actual, string $description = null): void
	{
		self::$counter++;
		if ($actual === null) {
			self::fail(self::describe('Value should not be NULL', $description));
		}
	}


	/**
	 * Asserts that a value is Not a Number.
	 * @param  mixed  $actual
	 */
	public static function nan($actual, string $description = null): void
	{
		self::$counter++;
		if (!is_float($actual) || !is_nan($actual)) {
			self::fail(self::describe('%1 should be NAN', $description), $actual);
		}
	}


	/**
	 * Asserts that a value is truthy.
	 * @param  mixed  $actual
	 */
	public static function truthy($actual, string $description = null): void
	{
		self::$counter++;
		if (!$actual) {
			self::fail(self::describe('%1 should be truthy', $description), $actual);
		}
	}


	/**
	 * Asserts that a value is falsey.
	 * @param  mixed  $actual
	 */
	public static function falsey($actual, string $description = null): void
	{
		self::$counter++;
		if ($actual) {
			self::fail(self::describe('%1 should be falsey', $description), $actual);
		}
	}


	/**
	 * Asserts the number of items in an array or Countable.
	 * @param  array|\Countable  $value
	 */
	public static function count(int $count, $value, string $description = null): void
	{
		self::$counter++;
		if (!$value instanceof \Countable && !is_array($value)) {
			self::fail(self::describe('%1 should be array or countable object', $description), $value);

		} elseif (count($value) !== $count) {
			self::fail(self::describe('Count %1 should be %2', $description), count($value), $count);
		}
	}


	/**
	 * Asserts that a value is of given class, interface or built-in type.
	 * @param  string|object  $type
	 * @param  mixed  $value
	 */
	public static function type($type, $value, string $description = null): void
	{
		self::$counter++;
		if (!is_object($type) && !is_string($type)) {
			throw new \Exception('Type must be a object or a string.');

		} elseif ($type === 'list') {
			if (!is_array($value) || ($value && array_keys($value) !== range(0, count($value) - 1))) {
				self::fail(self::describe("%1 should be $type", $description), $value);
			}
		} elseif (in_array($type, ['array', 'bool', 'callable', 'float',
			'int', 'integer', 'null', 'object', 'resource', 'scalar', 'string', ], true)
		) {
			if (!("is_$type")($value)) {
				self::fail(self::describe(gettype($value) . " should be $type", $description));
			}
		} elseif (!$value instanceof $type) {
			$actual = is_object($value) ? get_class($value) : gettype($value);
			$type = is_object($type) ? get_class($type) : $type;
			self::fail(self::describe("$actual should be instance of $type", $description));
		}
	}


	/**
	 * Asserts that a function throws exception of given type and its message matches given pattern.
	 */
	public static function exception(
		callable $function,
		string $class,
		string $message = null,
		$code = null
	): ?\Throwable {
		self::$counter++;
		$e = null;
		try {
			$function();
		} catch (\Throwable $e) {
		}

		if ($e === null) {
			self::fail("$class was expected, but none was thrown");

		} elseif (!$e instanceof $class) {
			self::fail("$class was expected but got " . get_class($e) . ($e->getMessage() ? " ({$e->getMessage()})" : ''), null, null, $e);

		} elseif ($message && !self::isMatching($message, $e->getMessage())) {
			self::fail("$class with a message matching %2 was expected but got %1", $e->getMessage(), $message);

		} elseif ($code !== null && $e->getCode() !== $code) {
			self::fail("$class with a code %2 was expected but got %1", $e->getCode(), $code);
		}

		return $e;
	}


	/**
	 * Asserts that a function throws exception of given type and its message matches given pattern. Alias for exception().
	 */
	public static function throws(callable $function, string $class, string $message = null, $code = null): ?\Throwable
	{
		return self::exception($function, $class, $message, $code);
	}


	/**
	 * Asserts that a function generates one or more PHP errors or throws exceptions.
	 * @param  int|string|array $expectedType
	 * @param  string $expectedMessage message
	 * @throws \Exception
	 * @throws \Exception
	 */
	public static function error(callable $function, $expectedType, string $expectedMessage = null): ?\Throwable
	{
		if (is_string($expectedType) && !preg_match('#^E_[A-Z_]+$#D', $expectedType)) {
			return static::exception($function, $expectedType, $expectedMessage);
		}

		self::$counter++;
		$expected = is_array($expectedType) ? $expectedType : [[$expectedType, $expectedMessage]];
		foreach ($expected as &$item) {
			$item = ((array) $item) + [null, null];
			$expectedType = $item[0];
			if (is_int($expectedType)) {
				$item[2] = Helpers::errorTypeToString($expectedType);
			} elseif (is_string($expectedType)) {
				$item[0] = constant($item[2] = $expectedType);
			} else {
				throw new \Exception('Error type must be E_* constant.');
			}
		}

		set_error_handler(function (int $severity, string $message, string $file, int $line) use (&$expected) {
			if (($severity & error_reporting()) !== $severity) {
				return;
			}

			$errorStr = Helpers::errorTypeToString($severity) . ($message ? " ($message)" : '');
			[$expectedType, $expectedMessage, $expectedTypeStr] = array_shift($expected);
			if ($expectedType === null) {
				self::fail("Generated more errors than expected: $errorStr was generated in file $file on line $line");

			} elseif ($severity !== $expectedType) {
				self::fail("$expectedTypeStr was expected, but $errorStr was generated in file $file on line $line");

			} elseif ($expectedMessage && !self::isMatching($expectedMessage, $message)) {
				self::fail("$expectedTypeStr with a message matching %2 was expected but got %1", $message, $expectedMessage);
			}
		});

		reset($expected);
		try {
			$function();
			restore_error_handler();
		} catch (\Throwable $e) {
			restore_error_handler();
			throw $e;
		}

		if ($expected) {
			self::fail('Error was expected, but was not generated');
		}

		return null;
	}


	/**
	 * Asserts that a function does not generate PHP errors and does not throw exceptions.
	 */
	public static function noError(callable $function): void
	{
		if (($count = func_num_args()) > 1) {
			throw new \Exception(__METHOD__ . "() expects 1 parameter, $count given.");
		}

		self::error($function, []);
	}


	/**
	 * Asserts that a string matches a given pattern.
	 *   %a%    one or more of anything except the end of line characters
	 *   %a?%   zero or more of anything except the end of line characters
	 *   %A%    one or more of anything including the end of line characters
	 *   %A?%   zero or more of anything including the end of line characters
	 *   %s%    one or more white space characters except the end of line characters
	 *   %s?%   zero or more white space characters except the end of line characters
	 *   %S%    one or more of characters except the white space
	 *   %S?%   zero or more of characters except the white space
	 *   %c%    a single character of any sort (except the end of line)
	 *   %d%    one or more digits
	 *   %d?%   zero or more digits
	 *   %i%    signed integer value
	 *   %f%    floating point number
	 *   %h%    one or more HEX digits
	 * @param  string  $pattern  mask|regexp; only delimiters ~ and # are supported for regexp
	 */
	public static function match(string $pattern, $actual, string $description = null): void
	{
		self::$counter++;
		if (!is_scalar($actual)) {
			self::fail(self::describe('%1 should match %2', $description), $actual, $pattern);

		} elseif (!self::isMatching($pattern, $actual)) {
			if (self::$expandPatterns) {
				[$pattern, $actual] = self::expandMatchingPatterns($pattern, $actual);
			}

			self::fail(self::describe('%1 should match %2', $description), $actual, $pattern);
		}
	}


	/**
	 * Asserts that a string matches a given pattern stored in file.
	 */
	public static function matchFile(string $file, $actual, string $description = null): void
	{
		self::$counter++;
		$pattern = @file_get_contents($file); // @ is escalated to exception
		if ($pattern === false) {
			throw new \Exception("Unable to read file '$file'.");

		} elseif (!is_scalar($actual)) {
			self::fail(self::describe('%1 should match %2', $description), $actual, $pattern, null, basename($file));

		} elseif (!self::isMatching($pattern, $actual)) {
			if (self::$expandPatterns) {
				[$pattern, $actual] = self::expandMatchingPatterns($pattern, $actual);
			}

			self::fail(self::describe('%1 should match %2', $description), $actual, $pattern, null, basename($file));
		}
	}


	/**
	 * Assertion that fails.
	 */
	public static function fail(
		string $message,
		$actual = null,
		$expected = null,
		\Throwable $previous = null,
		string $outputName = null
	): void {
		$e = new AssertException($message, $expected, $actual, $previous);
		$e->outputName = $outputName;
		if (self::$onFailure) {
			(self::$onFailure)($e);
		} else {
			throw $e;
		}
	}


	private static function describe(string $reason, string $description = null): string
	{
		return ($description ? $description . ': ' : '') . $reason;
	}


	/**
	 * Executes function that can access private and protected members of given object via $this.
	 * @param  object|string  $obj
	 */
	public static function with($objectOrClass, \Closure $closure)
	{
		return $closure->bindTo(is_object($objectOrClass) ? $objectOrClass : null, $objectOrClass)();
	}


	/********************* helpers ****************d*g**/


	/**
	 * Compares using mask.
	 * @internal
	 */
	public static function isMatching(string $pattern, $actual, bool $strict = false): bool
	{
		if (!is_scalar($actual)) {
			throw new \Exception('Value must be strings.');
		}

		$old = ini_set('pcre.backtrack_limit', '10000000');

		if (!self::isPcre($pattern)) {
			$utf8 = preg_match('#\x80-\x{10FFFF}]#u', $pattern) ? 'u' : '';
			$suffix = ($strict ? '$#DsU' : '\s*$#sU') . $utf8;
			$patterns = static::$patterns + [
				'[.\\\\+*?[^$(){|\#]' => '\$0', // preg quoting
				'\x00' => '\x00',
				'[\t ]*\r?\n' => '[\t ]*\r?\n', // right trim
			];
			$pattern = '#^' . preg_replace_callback('#' . implode('|', array_keys($patterns)) . '#U' . $utf8, function ($m) use ($patterns) {
				foreach ($patterns as $re => $replacement) {
					$s = preg_replace("#^$re$#D", str_replace('\\', '\\\\', $replacement), $m[0], 1, $count);
					if ($count) {
						return $s;
					}
				}
			}, rtrim($pattern, " \t\n\r")) . $suffix;
		}

		$res = preg_match($pattern, (string) $actual);
		ini_set('pcre.backtrack_limit', $old);
		if ($res === false || preg_last_error()) {
			throw new \Exception('Error while executing regular expression. (PREG Error Code ' . preg_last_error() . ')');
		}

		return (bool) $res;
	}


	/**
	 * @internal
	 */
	public static function expandMatchingPatterns(string $pattern, $actual): array
	{
		if (self::isPcre($pattern)) {
			return [$pattern, $actual];
		}

		$parts = preg_split('#(%)#', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);
		for ($i = count($parts); $i >= 0; $i--) {
			$patternX = implode(array_slice($parts, 0, $i));
			$patternY = "$patternX%A?%";
			if (self::isMatching($patternY, $actual)) {
				$patternZ = implode(array_slice($parts, $i));
				break;
			}
		}

		foreach (['%A%', '%A?%'] as $greedyPattern) {
			if (substr($patternX, -strlen($greedyPattern)) === $greedyPattern) {
				$patternX = substr($patternX, 0, -strlen($greedyPattern));
				$patternY = "$patternX%A?%";
				$patternZ = $greedyPattern . $patternZ;
				break;
			}
		}

		$low = 0;
		$high = strlen($actual);
		while ($low <= $high) {
			$mid = ($low + $high) >> 1;
			if (self::isMatching($patternY, substr($actual, 0, $mid))) {
				$high = $mid - 1;
			} else {
				$low = $mid + 1;
			}
		}

		$low = $high + 2;
		$high = strlen($actual);
		while ($low <= $high) {
			$mid = ($low + $high) >> 1;
			if (!self::isMatching($patternX, substr($actual, 0, $mid), true)) {
				$high = $mid - 1;
			} else {
				$low = $mid + 1;
			}
		}

		$actualX = substr($actual, 0, $high);
		$actualZ = substr($actual, $high);

		return [
			$actualX . rtrim(preg_replace('#[\t ]*\r?\n#', "\n", $patternZ)),
			$actualX . rtrim(preg_replace('#[\t ]*\r?\n#', "\n", $actualZ)),
		];
	}


	/**
	 * Compares two structures and checks expectations. The identity of objects, the order of keys
	 * in the arrays and marginally different floats are ignored.
	 */
	private static function isEqual($expected, $actual, int $level = 0, $objects = null): bool
	{
		switch (true) {
			case $level > 10:
				throw new \Exception('Nesting level too deep or recursive dependency.');

			case $expected instanceof Expect:
				$expected($actual);
				return true;

			case is_float($expected) && is_float($actual) && is_finite($expected) && is_finite($actual):
				$diff = abs($expected - $actual);
				return ($diff < self::EPSILON) || ($diff / max(abs($expected), abs($actual)) < self::EPSILON);

			case is_object($expected) && is_object($actual) && get_class($expected) === get_class($actual):
				$objects = $objects ? clone $objects : new \SplObjectStorage;
				if (isset($objects[$expected])) {
					return $objects[$expected] === $actual;
				} elseif ($expected === $actual) {
					return true;
				}

				$objects[$expected] = $actual;
				$objects[$actual] = $expected;
				$expected = (array) $expected;
				$actual = (array) $actual;
				// break omitted

			case is_array($expected) && is_array($actual):
				ksort($expected, SORT_STRING);
				ksort($actual, SORT_STRING);
				if (array_keys($expected) !== array_keys($actual)) {
					return false;
				}

				foreach ($expected as $value) {
					if (!self::isEqual($value, current($actual), $level + 1, $objects)) {
						return false;
					}

					next($actual);
				}

				return true;

			default:
				return $expected === $actual;
		}
	}


	private static function isPcre(string $pattern): bool
	{
		return (bool) preg_match('/^([~#]).+(\1)[imsxUu]*$/Ds', $pattern);
	}
}
