<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester;


/**
 * Assertion test helpers.
 */
class Assert
{
	/** used by equal() for comparing floats */
	const EPSILON = 1e-10;

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

	/** @var callable  function (AssertException $exception) */
	public static $onFailure;

	/** @var int  the count of assertions */
	public static $counter = 0;


	/**
	 * Checks assertion. Values must be exactly the same.
	 * @return void
	 */
	public static function same($expected, $actual, $description = null)
	{
		self::$counter++;
		if ($actual !== $expected) {
			self::fail(self::describe('%1 should be %2', $description), $actual, $expected);
		}
	}


	/**
	 * Checks assertion. Values must not be exactly the same.
	 * @return void
	 */
	public static function notSame($expected, $actual, $description = null)
	{
		self::$counter++;
		if ($actual === $expected) {
			self::fail(self::describe('%1 should not be %2', $description), $actual, $expected);
		}
	}


	/**
	 * Checks assertion. The identity of objects and the order of keys in the arrays are ignored.
	 * @return void
	 */
	public static function equal($expected, $actual, $description = null)
	{
		self::$counter++;
		if (!self::isEqual($expected, $actual)) {
			self::fail(self::describe('%1 should be equal to %2', $description), $actual, $expected);
		}
	}


	/**
	 * Checks assertion. The identity of objects and the order of keys in the arrays are ignored.
	 * @return void
	 */
	public static function notEqual($expected, $actual, $description = null)
	{
		self::$counter++;
		if (self::isEqual($expected, $actual)) {
			self::fail(self::describe('%1 should not be equal to %2', $description), $actual, $expected);
		}
	}


	/**
	 * Checks assertion. Values must contains expected needle.
	 * @return void
	 */
	public static function contains($needle, $actual, $description = null)
	{
		self::$counter++;
		if (is_array($actual)) {
			if (!in_array($needle, $actual, true)) {
				self::fail(self::describe('%1 should contain %2', $description), $actual, $needle);
			}
		} elseif (is_string($actual)) {
			if ($needle !== '' && strpos($actual, $needle) === false) {
				self::fail(self::describe('%1 should contain %2', $description), $actual, $needle);
			}
		} else {
			self::fail(self::describe('%1 should be string or array', $description), $actual);
		}
	}


	/**
	 * Checks assertion. Values must not contains expected needle.
	 * @return void
	 */
	public static function notContains($needle, $actual, $description = null)
	{
		self::$counter++;
		if (is_array($actual)) {
			if (in_array($needle, $actual, true)) {
				self::fail(self::describe('%1 should not contain %2', $description), $actual, $needle);
			}
		} elseif (is_string($actual)) {
			if ($needle === '' || strpos($actual, $needle) !== false) {
				self::fail(self::describe('%1 should not contain %2', $description), $actual, $needle);
			}
		} else {
			self::fail(self::describe('%1 should be string or array', $description), $actual);
		}
	}


	/**
	 * Checks TRUE assertion.
	 * @param  mixed  actual
	 * @param  string  fail message
	 * @return void
	 */
	public static function true($actual, $description = null)
	{
		self::$counter++;
		if ($actual !== true) {
			self::fail(self::describe('%1 should be TRUE', $description), $actual);
		}
	}


	/**
	 * Checks FALSE assertion.
	 * @param  mixed  actual
	 * @param  string  fail message
	 * @return void
	 */
	public static function false($actual, $description = null)
	{
		self::$counter++;
		if ($actual !== false) {
			self::fail(self::describe('%1 should be FALSE', $description), $actual);
		}
	}


	/**
	 * Checks NULL assertion.
	 * @param  mixed  actual
	 * @param  string  fail message
	 * @return void
	 */
	public static function null($actual, $description = null)
	{
		self::$counter++;
		if ($actual !== null) {
			self::fail(self::describe('%1 should be NULL', $description), $actual);
		}
	}


	/**
	 * Checks Not a Number assertion.
	 * @param  mixed  actual
	 * @param  string  fail message
	 * @return void
	 */
	public static function nan($actual, $description = null)
	{
		self::$counter++;
		if (!is_float($actual) || !is_nan($actual)) {
			self::fail(self::describe('%1 should be NAN', $description), $actual);
		}
	}


	/**
	 * Checks truthy assertion.
	 * @param  mixed  actual
	 * @param  string  fail message
	 * @return void
	 */
	public static function truthy($actual, $description = null)
	{
		self::$counter++;
		if (!$actual) {
			self::fail(self::describe('%1 should be truthy', $description), $actual);
		}
	}


	/**
	 * Checks falsey (empty) assertion.
	 * @param  mixed  actual
	 * @param  string  fail message
	 * @return void
	 */
	public static function falsey($actual, $description = null)
	{
		self::$counter++;
		if ($actual) {
			self::fail(self::describe('%1 should be falsey', $description), $actual);
		}
	}


	/**
	 * Checks if subject has expected count.
	 * @param  int    expected count
	 * @param  mixed  subject
	 * @param  string  fail message
	 * @return void
	 */
	public static function count($count, $value, $description = null)
	{
		self::$counter++;
		if (!$value instanceof \Countable && !is_array($value)) {
			self::fail(self::describe('%1 should be array or countable object', $description), $value);

		} elseif (count($value) !== $count) {
			self::fail(self::describe('Count %1 should be %2', $description), count($value), $count);
		}
	}


	/**
	 * Checks assertion.
	 * @return void
	 */
	public static function type($type, $value, $description = null)
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
			if (!call_user_func("is_$type", $value)) {
				self::fail(self::describe(gettype($value) . " should be $type", $description));
			}

		} elseif (!$value instanceof $type) {
			$actual = is_object($value) ? get_class($value) : gettype($value);
			self::fail(self::describe("$actual should be instance of $type", $description));
		}
	}


	/**
	 * Checks if the function throws exception.
	 * @param  callable
	 * @param  string class
	 * @param  string message
	 * @param  int code
	 * @return \Exception|\Throwable
	 */
	public static function exception(callable $function, $class, $message = null, $code = null)
	{
		self::$counter++;
		$e = null;
		try {
			call_user_func($function);
		} catch (\Exception $e) {
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
	 * Checks if the function throws exception, alias for exception().
	 * @return \Exception|\Throwable
	 */
	public static function throws(callable $function, $class, $message = null, $code = null)
	{
		return self::exception($function, $class, $message, $code);
	}


	/**
	 * Checks if the function generates PHP error or throws exception.
	 * @param  callable
	 * @param  int|string|array
	 * @param  string message
	 * @return null|\Exception|\Throwable
	 */
	public static function error(callable $function, $expectedType, $expectedMessage = null)
	{
		if (is_string($expectedType) && !preg_match('#^E_[A-Z_]+\z#', $expectedType)) {
			return static::exception($function, $expectedType, $expectedMessage);
		}

		self::$counter++;
		$expected = is_array($expectedType) ? $expectedType : [[$expectedType, $expectedMessage]];
		foreach ($expected as &$item) {
			list($expectedType, $expectedMessage) = $item;
			if (is_int($expectedType)) {
				$item[2] = Helpers::errorTypeToString($expectedType);
			} elseif (is_string($expectedType)) {
				$item[0] = constant($item[2] = $expectedType);
			} else {
				throw new \Exception('Error type must be E_* constant.');
			}
		}

		set_error_handler(function ($severity, $message, $file, $line) use (&$expected) {
			if (($severity & error_reporting()) !== $severity) {
				return;
			}

			$errorStr = Helpers::errorTypeToString($severity) . ($message ? " ($message)" : '');
			list($expectedType, $expectedMessage, $expectedTypeStr) = array_shift($expected);
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
			call_user_func($function);
			restore_error_handler();
		} catch (\Exception $e) {
			restore_error_handler();
			throw $e;
		}

		if ($expected) {
			self::fail('Error was expected, but was not generated');
		}
	}


	/**
	 * Checks that the function does not generate PHP error and does not throw exception.
	 * @param  callable
	 * @return void
	 */
	public static function noError($function)
	{
		self::error($function, []);
	}


	/**
	 * Compares result using regular expression or mask:
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
	 * @param  string  mask|regexp; only delimiters ~ and # are supported for regexp
	 * @param  string actual
	 * @param  string  fail message
	 * @return void
	 */
	public static function match($pattern, $actual, $description = null)
	{
		self::$counter++;
		if (!is_string($pattern)) {
			throw new \Exception('Pattern must be a string.');

		} elseif (!is_scalar($actual)) {
			self::fail(self::describe('%1 should match %2', $description), $actual, $pattern);

		} elseif (!self::isMatching($pattern, $actual)) {
			list($pattern, $actual) = self::expandMatchingPatterns($pattern, $actual);
			self::fail(self::describe('%1 should match %2', $description), $actual, $pattern);
		}
	}


	/**
	 * Compares results using mask sorted in file.
	 * @return void
	 */
	public static function matchFile($file, $actual, $description = null)
	{
		self::$counter++;
		$pattern = @file_get_contents($file); // @ is escalated to exception
		if ($pattern === false) {
			throw new \Exception("Unable to read file '$file'.");

		} elseif (!is_scalar($actual)) {
			self::fail(self::describe('%1 should match %2', $description), $actual, $pattern);

		} elseif (!self::isMatching($pattern, $actual)) {
			list($pattern, $actual) = self::expandMatchingPatterns($pattern, $actual);
			self::fail(self::describe('%1 should match %2', $description), $actual, $pattern);
		}
	}


	/**
	 * Failed assertion
	 * @return void
	 */
	public static function fail($message, $actual = null, $expected = null, $previous = null)
	{
		$e = new AssertException($message, $expected, $actual, $previous);
		if (self::$onFailure) {
			call_user_func(self::$onFailure, $e);
		} else {
			throw $e;
		}
	}


	private static function describe($reason, $description)
	{
		return ($description ? $description . ': ' : '') . $reason;
	}


	public static function with($obj, \Closure $closure)
	{
		return $closure->bindTo($obj, $obj)->__invoke();
	}


	/********************* helpers ****************d*g**/


	/**
	 * Compares using mask.
	 * @return bool
	 * @internal
	 */
	public static function isMatching($pattern, $actual, $strict = false)
	{
		if (!is_string($pattern) || !is_scalar($actual)) {
			throw new \Exception('Value and pattern must be strings.');
		}

		$old = ini_set('pcre.backtrack_limit', '10000000');

		if (!self::isPcre($pattern)) {
			$utf8 = preg_match('#\x80-\x{10FFFF}]#u', $pattern) ? 'u' : '';
			$suffix = ($strict ? '\z#sU' : '\s*$#sU') . $utf8;
			$patterns = static::$patterns + [
				'[.\\\\+*?[^$(){|\#]' => '\$0', // preg quoting
				'\x00' => '\x00',
				'[\t ]*\r?\n' => '[\t ]*\r?\n', // right trim
			];
			$pattern = '#^' . preg_replace_callback('#' . implode('|', array_keys($patterns)) . '#U' . $utf8, function ($m) use ($patterns) {
				foreach ($patterns as $re => $replacement) {
					$s = preg_replace("#^$re\\z#", str_replace('\\', '\\\\', $replacement), $m[0], 1, $count);
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
	 * @return array
	 * @internal
	 */
	public static function expandMatchingPatterns($pattern, $actual)
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
	 * Compares two structures. Ignores the identity of objects and the order of keys in the arrays.
	 * @return bool
	 */
	private static function isEqual($expected, $actual, $level = 0, $objects = null)
	{
		if ($level > 10) {
			throw new \Exception('Nesting level too deep or recursive dependency.');
		}

		if (is_float($expected) && is_float($actual) && is_finite($expected) && is_finite($actual)) {
			$diff = abs($expected - $actual);
			return ($diff < self::EPSILON) || ($diff / max(abs($expected), abs($actual)) < self::EPSILON);
		}

		if (is_object($expected) && is_object($actual) && get_class($expected) === get_class($actual)) {
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
		}

		if (is_array($expected) && is_array($actual)) {
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
		}

		return $expected === $actual;
	}


	/**
	 * @param  string
	 * @return bool
	 */
	private static function isPcre($pattern)
	{
		return (bool) preg_match('/^([~#]).+(\1)[imsxUu]*\z/s', $pattern);
	}
}
