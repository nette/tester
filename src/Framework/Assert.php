<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
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

	/** @var int  the count of assertions */
	public static $counter = 0;


	/**
	 * Checks assertion. Values must be exactly the same.
	 * @return void
	 */
	public static function same($expected, $actual)
	{
		self::$counter++;
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
		self::$counter++;
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
		self::$counter++;
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
		self::$counter++;
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
		self::$counter++;
		if (is_array($actual)) {
			if (!in_array($needle, $actual, TRUE)) {
				self::fail('%1 should contain %2', $actual, $needle);
			}
		} elseif (is_string($actual)) {
			if ($needle !== '' && strpos($actual, $needle) === FALSE) {
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
		self::$counter++;
		if (is_array($actual)) {
			if (in_array($needle, $actual, TRUE)) {
				self::fail('%1 should not contain %2', $actual, $needle);
			}
		} elseif (is_string($actual)) {
			if ($needle === '' || strpos($actual, $needle) !== FALSE) {
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
		self::$counter++;
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
		self::$counter++;
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
		self::$counter++;
		if ($actual !== NULL) {
			self::fail('%1 should be NULL', $actual);
		}
	}


	/**
	 * Checks Not a Number assertion.
	 * @param  mixed  actual
	 * @return void
	 */
	public static function nan($actual)
	{
		self::$counter++;
		if (!is_float($actual) || !is_nan($actual)) {
			self::fail('%1 should be NAN', $actual);
		}
	}


	/**
	 * Checks truthy assertion.
	 * @param  mixed  actual
	 * @return void
	 */
	public static function truthy($actual)
	{
		self::$counter++;
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
		self::$counter++;
		if ($actual) {
			self::fail('%1 should be falsey', $actual);
		}
	}


	/**
	 * Checks if subject has expected count.
	 * @param  int    expected count
	 * @param  mixed  subject
	 * @return void
	 */
	public static function count($count, $value)
	{
		self::$counter++;
		if (!$value instanceof \Countable && !is_array($value)) {
			self::fail('%1 should be array or countable object', $value);

		} elseif (count($value) !== $count) {
			self::fail('Count %1 should be %2', count($value), $count);
		}
	}


	/**
	 * Checks assertion.
	 * @return void
	 */
	public static function type($type, $value)
	{
		self::$counter++;
		if (!is_object($type) && !is_string($type)) {
			throw new \Exception('Type must be a object or a string.');

		} elseif ($type === 'list') {
			if (!is_array($value) || ($value && array_keys($value) !== range(0, count($value) - 1))) {
				self::fail("%1 should be $type", $value);
			}

		} elseif (in_array($type, array('array', 'bool', 'callable', 'float',
			'int', 'integer', 'numeric', 'null', 'object', 'resource', 'scalar', 'string'), TRUE)
		) {
			if (!call_user_func("is_$type", $value)) {
				self::fail(gettype($value) . " should be $type");
			}

		} elseif (!$value instanceof $type) {
			$actual = is_object($value) ? get_class($value) : gettype($value);
			self::fail("$actual should be instance of $type");
		}
	}


	/**
	 * Checks if the function throws exception.
	 * @param  callable
	 * @param  string class
	 * @param  string message
	 * @param  integer code
	 * @return \Exception
	 */
	public static function exception($function, $class, $message = NULL, $code = NULL)
	{
		self::$counter++;
		$e = NULL;
		try {
			call_user_func($function);
		} catch (\Exception $e) {
		}
		if ($e === NULL) {
			self::fail("$class was expected, but none was thrown");

		} elseif (!$e instanceof $class) {
			self::fail("$class was expected but got " . get_class($e) . ($e->getMessage() ? " ({$e->getMessage()})" : ''));

		} elseif ($message && !self::isMatching($message, $e->getMessage())) {
			self::fail("$class with a message matching %2 was expected but got %1", $e->getMessage(), $message);

		} elseif ($code !== NULL && $e->getCode() !== $code) {
			self::fail("$class with a code %2 was expected but got %1", $e->getCode(), $code);
		}
		return $e;
	}


	/**
	 * Checks if the function throws exception, alias for exception().
	 * @return \Exception
	 */
	public static function throws($function, $class, $message = NULL, $code = NULL)
	{
		return self::exception($function, $class, $message, $code);
	}


	/**
	 * Checks if the function generates PHP error or throws exception.
	 * @param  callable
	 * @param  int|string|array
	 * @param  string message
	 * @return null|\Exception
	 */
	public static function error($function, $expectedType, $expectedMessage = NULL)
	{
		if (is_string($expectedType) && !preg_match('#^E_[A-Z_]+\z#', $expectedType)) {
			return static::exception($function, $expectedType, $expectedMessage);
		}

		self::$counter++;
		$expected = is_array($expectedType) ? $expectedType : array(array($expectedType, $expectedMessage));
		foreach ($expected as & $item) {
			list($expectedType, $expectedMessage) = $item;
			if (is_int($expectedType)) {
				$item[2] = Helpers::errorTypeToString($expectedType);
			} elseif (is_string($expectedType)) {
				$item[0] = constant($item[2] = $expectedType);
			} else {
				throw new \Exception('Error type must be E_* constant.');
			}
		}

		set_error_handler(function($severity, $message, $file, $line) use (& $expected) {
			if (($severity & error_reporting()) !== $severity) {
				return;
			}

			$errorStr = Helpers::errorTypeToString($severity) . ($message ? " ($message)" : '');
			list($expectedType, $expectedMessage, $expectedTypeStr) = array_shift($expected);
			if ($expectedType === NULL) {
				restore_error_handler();
				Assert::fail("Generated more errors than expected: $errorStr was generated in file $file on line $line");

			} elseif ($severity !== $expectedType) {
				restore_error_handler();
				Assert::fail("$expectedTypeStr was expected, but $errorStr was generated in file $file on line $line");

			} elseif ($expectedMessage && !Assert::isMatching($expectedMessage, $message)) {
				restore_error_handler();
				Assert::fail("$expectedTypeStr with a message matching %2 was expected but got %1", $message, $expectedMessage);
			}
		});

		reset($expected);
		call_user_func($function);
		restore_error_handler();

		if ($expected) {
			self::fail('Error was expected, but was not generated');
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
		self::$counter++;
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
		self::$counter++;
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
		$e = new AssertException($message, $expected, $actual);
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
	public static function isEqual($expected, $actual, $level = 0, $objects = NULL)
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
				return TRUE;
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
				return FALSE;
			}

			foreach ($expected as $value) {
				if (!self::isEqual($value, current($actual), $level + 1, $objects)) {
					return FALSE;
				}
				next($actual);
			}
			return TRUE;
		}

		return $expected === $actual;
	}

}


/**
 * Assertion exception.
 */
class AssertException extends \Exception
{
	public $origMessage;

	public $actual;

	public $expected;


	public function __construct($message, $expected, $actual)
	{
		parent::__construct();
		$this->expected = $expected;
		$this->actual = $actual;
		$this->setMessage($message);
	}


	public function setMessage($message)
	{
		$this->origMessage = $message;
		$this->message = strtr($message, array(
			'%1' => Dumper::toLine($this->actual),
			'%2' => Dumper::toLine($this->expected),
		));
		return $this;
	}

}
