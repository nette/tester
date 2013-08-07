<?php

/**
 * This file is part of the Nette Tester.
 *
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Tester;


/**
 * Assertion test helpers.
 *
 * @author     David Grudl
 */
class Assert
{
	/** used by equal() for comparing floats */
	const EPSILON = 1e-10;

	/** used by match(); in values, each $ followed by number is backreference */
	static public $patterns = array(
		'%a%' => '[^\r\n]+',    // one or more of anything except the end of line characters
		'%a\?%'=> '[^\r\n]*',   // zero or more of anything except the end of line characters
		'%A%' => '.+',          // one or more of anything including the end of line characters
		'%A\?%'=> '.*',         // zero or more of anything including the end of line characters
		'%s%' => '[\t ]+',      // one or more white space characters except the end of line characters
		'%s\?%'=> '[\t ]*',     // zero or more white space characters except the end of line characters
		'%S%' => '\S+',         // one or more of characters except the white space
		'%S\?%'=> '\S*',        // zero or more of characters except the white space
		'%c%' => '[^\r\n]',     // a single character of any sort (except the end of line)
		'%d%' => '[0-9]+',      // one or more digits
		'%d\?%'=> '[0-9]*',     // zero or more digits
		'%i%' => '[+-]?[0-9]+', // signed integer value
		'%f%' => '[+-]?\.?\d+\.?\d*(?:[Ee][+-]?\d+)?', // floating point number
		'%h%' => '[0-9a-fA-F]+',// one or more HEX digits
		'%ds%'=> '[\\\\/]', // directory separator
		'%(\[.*\].*)%'=> '$1',  // range
	);

	/** @var callable  function(AssertException $exception) */
	public static $onFailure;


	/**
	 * Checks assertion. Values must be exactly the same.
	 * @return void
	 */
	public static function same($expected, $actual)
	{
		if ($actual !== $expected) {
			self::fail('%1 should be %2', $actual, $expected);
		}
	}


	/**
	 * Checks assertion. Values must not be exactly the same.
	 * @return void
	 */
	public static function notSame($expected, $actual)
	{
		if ($actual === $expected) {
			self::fail('%1 should not be %2', $actual, $expected);
		}
	}


	/**
	 * Checks assertion. The identity of objects and the order of keys in the arrays are ignored.
	 * @return void
	 */
	public static function equal($expected, $actual)
	{
		if (!self::isEqual($expected, $actual)) {
			self::fail('%1 should be equal to %2', $actual, $expected);
		}
	}


	/**
	 * Checks assertion. The identity of objects and the order of keys in the arrays are ignored.
	 * @return void
	 */
	public static function notEqual($expected, $actual)
	{
		if (self::isEqual($expected, $actual)) {
			self::fail('%1 should not be equal to %2', $actual, $expected);
		}
	}


	/**
	 * Checks assertion. Values must contains expected needle.
	 * @return void
	 */
	public static function contains($needle, $actual)
	{
		if (is_array($actual)) {
			if (!in_array($needle, $actual, TRUE)) {
				self::fail('%1 should contain %2', $actual, $needle);
			}
		} elseif (is_string($actual)) {
			if (strpos($actual, $needle) === FALSE) {
				self::fail('%1 should contain %2', $actual, $needle);
			}
		} else {
			self::fail('%1 should be string or array', $actual);
		}
	}


	/**
	 * Checks assertion. Values must not contains expected needle.
	 * @return void
	 */
	public static function notContains($needle, $actual)
	{
		if (is_array($actual)) {
			if (in_array($needle, $actual, TRUE)) {
				self::fail('%1 should not contain %2', $actual, $needle);
			}
		} elseif (is_string($actual)) {
			if (strpos($actual, $needle) !== FALSE) {
				self::fail('%1 should not contain %2', $actual, $needle);
			}
		} else {
			self::fail('%1 should be string or array', $actual);
		}
	}


	/**
	 * Checks TRUE assertion.
	 * @param  mixed  actual
	 * @return void
	 */
	public static function true($actual)
	{
		if ($actual !== TRUE) {
			self::fail('%1 should be TRUE', $actual);
		}
	}


	/**
	 * Checks FALSE assertion.
	 * @param  mixed  actual
	 * @return void
	 */
	public static function false($actual)
	{
		if ($actual !== FALSE) {
			self::fail('%1 should be FALSE', $actual);
		}
	}


	/**
	 * Checks NULL assertion.
	 * @param  mixed  actual
	 * @return void
	 */
	public static function null($actual)
	{
		if ($actual !== NULL) {
			self::fail('%1 should be NULL', $actual);
		}
	}


	/**
	 * Checks truthy assertion.
	 * @param  mixed  actual
	 * @return void
	 */
	public static function truthy($actual)
	{
		if (!$actual) {
			self::fail('%1 should be truthy', $actual);
		}
	}


	/**
	 * Checks falsey (empty) assertion.
	 * @param  mixed  actual
	 * @return void
	 */
	public static function falsey($actual)
	{
		if ($actual) {
			self::fail('%1 should be falsey', $actual);
		}
	}


	/**
	 * Checks assertion.
	 * @return void
	 */
	public static function type($type, $value)
	{
		if (!is_object($type) && !is_string($type)) {
			throw new \Exception('Type must be a object or a string.');

		} elseif ($type === 'list') {
			if (!is_array($value) || ($value && array_keys($value) !== range(0, count($value) - 1))) {
				self::fail("%1 should be $type", $value);
			}

		} elseif (in_array($type, array('array', 'bool', 'callable', 'float',
			'int', 'integer', 'null', 'object', 'resource', 'scalar', 'string'), TRUE)
		) {
			if (!call_user_func("is_$type", $value)) {
				self::fail("%1 should be $type", $value);
			}

		} elseif (!$value instanceof $type) {
			self::fail("%1 should be instance of $type", $value);
		}
	}


	/**
	 * Checks if the function throws exception.
	 * @param  callable
	 * @param  string class
	 * @param  string message
	 * @return Exception
	 */
	public static function exception($function, $class, $message = NULL)
	{
		try {
			call_user_func($function);
		} catch (\Exception $e) {
		}
		if (!isset($e)) {
			self::fail("$class was expected, but none was thrown");

		} elseif (!$e instanceof $class) {
			self::fail("$class was expected but got " . get_class($e) . ($e->getMessage() ? " ({$e->getMessage()})" : ''));

		} elseif ($message && !self::isMatching($message, $e->getMessage())) {
			self::fail("$class with a message matching %2 was expected but got %1", $e->getMessage(), $message);
		}
		return $e;
	}


	/**
	 * Checks if the function throws exception, alias for exception().
	 * @return Exception
	 */
	public static function throws($function, $class, $message = NULL)
	{
		return self::exception($function, $class, $message);
	}


	/**
	 * Checks if the function generates PHP error or throws exception.
	 * @param  callable
	 * @param  int|string
	 * @param  string message
	 * @return void
	 */
	public static function error($function, $expectedType, $expectedMessage = NULL)
	{
		if (is_int($expectedType)) {
			$expectedTypeStr = self::errorTypeToString($expectedType);

		} elseif (!is_string($expectedType)) {
			throw new \Exception('Error type must be E_* constant or Exception class name.');

		} elseif (preg_match('#^E_[A-Z_]+\z#', $expectedType)) {
			$expectedType = constant($expectedTypeStr = $expectedType);
		} else {
			return static::exception($function, $expectedType, $expectedMessage);
		}

		$catched = FALSE;
		set_error_handler(function($severity, $message, $file, $line) use (& $catched, $expectedType, $expectedMessage, $expectedTypeStr) {
			$errorStr = Assert::errorTypeToString($severity) . ($message ? " ($message)" : '');
			if (($severity & error_reporting()) !== $severity) {
				return;

			} elseif ($catched) {
				Assert::fail("Expected one $expectedTypeStr, but another $errorStr was generated in file $file on line $line");

			} elseif ($severity !== $expectedType) {
				Assert::fail("$expectedTypeStr was expected, but $errorStr was generated in file $file on line $line");

			} elseif ($expectedMessage && !Assert::isMatching($expectedMessage, $message)) {
				Assert::fail("$expectedTypeStr with a message matching %2 was expected but got %1", $message, $expectedMessage);
			}
			$catched = TRUE;
		});
		call_user_func($function);
		restore_error_handler();
		if (!$catched) {
			self::fail("$expectedTypeStr was expected, but none was generated");
		}
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
	 * @param  string
	 * @return void
	 */
	public static function match($pattern, $actual)
	{
		if (!is_string($pattern)) {
			throw new \Exception('Pattern must be a string.');

		} elseif (!is_scalar($actual) || !self::isMatching($pattern, $actual)) {
			self::fail('%1 should match %2', $actual, rtrim($pattern));
		}
	}


	/**
	 * Compares results using mask sorted in file.
	 * @return void
	 */
	public static function matchFile($file, $actual)
	{
		$pattern = @file_get_contents($file);
		if ($pattern === FALSE) {
			throw new \Exception("Unable to read file '$file'.");

		} elseif (!is_scalar($actual) || !self::isMatching($pattern, $actual)) {
			self::fail('%1 should match %2', $actual, rtrim($pattern));
		}
	}


	/**
	 * Failed assertion
	 * @return void
	 */
	public static function fail($message, $actual = NULL, $expected = NULL)
	{
		$e = new AssertException($message);
		$e->actual = $actual;
		$e->expected = $expected;
		if (self::$onFailure) {
			call_user_func(self::$onFailure, $e);
		} else {
			throw $e;
		}
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
	public static function isMatching($pattern, $actual)
	{
		if (!is_string($pattern) && !is_scalar($actual)) {
			throw new \Exception('Value and pattern must be strings.');
		}

		$old = ini_set('pcre.backtrack_limit', '10000000');

		if (!preg_match('/^([~#]).+(\1)[imsxUu]*\z/s', $pattern)) {
			$utf8 = preg_match('#\x80-\x{10FFFF}]#u', $pattern) ? 'u' : '';
			$patterns = static::$patterns + array(
				'[.\\\\+*?[^$(){|\x00\#]' => '\$0', // preg quoting
				'[\t ]*\r?\n' => "[\\t ]*\n", // right trim
			);
			$pattern = '#^' . preg_replace_callback('#' . implode('|', array_keys($patterns)) . '#U' . $utf8, function($m) use ($patterns) {
				foreach ($patterns as $re => $replacement) {
					$s = preg_replace("#^$re\\z#", str_replace('\\', '\\\\', $replacement), $m[0], 1, $count);
					if ($count) {
						return $s;
					}
				}
			}, rtrim($pattern)) . '\s*$#sU' . $utf8;
			$actual = str_replace("\r\n", "\n", $actual);
		}

		$res = preg_match($pattern, $actual);
		ini_set('pcre.backtrack_limit', $old);
		if ($res === FALSE || preg_last_error()) {
			throw new \Exception('Error while executing regular expression. (PREG Error Code ' . preg_last_error() . ')');
		}
		return (bool) $res;
	}


	/**
	 * Compares two structures. Ignores the identity of objects and the order of keys in the arrays.
	 * @return bool
	 * @internal
	 */
	public static function isEqual($expected, $actual, $level = 0)
	{
		if ($level > 10) {
			throw new \Exception('Nesting level too deep or recursive dependency.');
		}

		if (is_float($expected) && is_float($actual)) {
			return abs($expected - $actual) < self::EPSILON;
		}

		if (is_object($expected) && is_object($actual) && get_class($expected) === get_class($actual)) {
			$expected = (array) $expected;
			$actual = (array) $actual;
		}

		if (is_array($expected) && is_array($actual)) {
			ksort($expected);
			ksort($actual);
			if (array_keys($expected) !== array_keys($actual)) {
				return FALSE;
			}

			foreach ($expected as $value) {
				if (!self::isEqual($value, current($actual), $level + 1)) {
					return FALSE;
				}
				next($actual);
			}
			return TRUE;
		}
		return $expected === $actual;
	}


	/**
	 * @internal
	 */
	/*private*/ static function errorTypeToString($type)
	{
		$consts = get_defined_constants(TRUE);
		foreach ($consts['Core'] as $name => $val) {
			if ($type === $val && substr($name, 0, 2) === 'E_') {
				return $name;
			}
		}
	}

}


/**
 * Assertion exception.
 *
 * @author     David Grudl
 */
class AssertException extends \Exception
{
	public $message;

	public $actual;

	public $expected;

}
