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
	const EPSILON = 1e-10;

	/** @var callable  function($message, $expected, $actual) */
	public static $onFailure = array(__CLASS__, 'assertionFailed');


	/**
	 * Checks assertion. Values must be exactly the same.
	 * @return void
	 */
	public static function same($expected, $actual)
	{
		if ($actual !== $expected) {
			self::fail(Dumper::toLine($actual) . ' should be ' . Dumper::toLine($expected), $expected, $actual);
		}
	}


	/**
	 * Checks assertion. Values must not be exactly the same.
	 * @return void
	 */
	public static function notSame($expected, $actual)
	{
		if ($actual === $expected) {
			self::fail(Dumper::toLine($actual) . ' should not be ' . Dumper::toLine($expected), $expected, $actual);
		}
	}


	/**
	 * Checks assertion. The identity of objects and the order of keys in the arrays are ignored.
	 * @return void
	 */
	public static function equal($expected, $actual)
	{
		if (!self::isEqual($expected, $actual)) {
			self::fail(Dumper::toLine($actual) . ' should be equal to ' . Dumper::toLine($expected), $expected, $actual);
		}
	}


	/**
	 * Checks assertion. The identity of objects and the order of keys in the arrays are ignored.
	 * @return void
	 */
	public static function notEqual($expected, $actual)
	{
		if (self::isEqual($expected, $actual)) {
			self::fail(Dumper::toLine($actual) . ' should not be equal to ' . Dumper::toLine($expected), $expected, $actual);
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
				self::fail(Dumper::toLine($actual) . ' should contain ' . Dumper::toLine($needle), $needle, $actual);
			}
		} elseif (is_string($actual)) {
			if (strpos($actual, $needle) === FALSE) {
				self::fail(Dumper::toLine($actual) . ' should contain ' . Dumper::toLine($needle), $needle, $actual);
			}
		} else {
			self::fail(Dumper::toLine($actual) . ' should be string or array');
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
				self::fail(Dumper::toLine($actual) . ' should not contain ' . Dumper::toLine($needle), $needle, $actual);
			}
		} elseif (is_string($actual)) {
			if (strpos($actual, $needle) !== FALSE) {
				self::fail(Dumper::toLine($actual) . ' should not contain ' . Dumper::toLine($needle), $needle, $actual);
			}
		} else {
			self::fail(Dumper::toLine($actual) . ' should be string or array');
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
			self::fail(Dumper::toLine($actual) . ' should be TRUE');
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
			self::fail(Dumper::toLine($actual) . ' should be FALSE');
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
			self::fail(Dumper::toLine($actual) . ' should be NULL');
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
				self::fail(Dumper::toLine($value) . " should be $type");
			}

		} elseif (in_array($type, array('array', 'bool', 'callable', 'float',
			'int', 'integer', 'null', 'object', 'resource', 'scalar', 'string'), TRUE)
		) {
			if (!call_user_func("is_$type", $value)) {
				self::fail(Dumper::toLine($value) . " should be $type");
			}

		} elseif (!$value instanceof $type) {
			self::fail(Dumper::toLine($value) . " should be instance of $type");
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
			self::fail("$class with a message matching '$message' was expected but got " . Dumper::toLine($e->getMessage()), $message, $e->getMessage());
		}
		return $e;
	}


	/**
	 * Checks if the function generates error.
	 * @param  callable
	 * @param  int
	 * @param  string message
	 * @return void
	 */
	public static function error($function, $expectedType, $expectedMessage = NULL)
	{
		$expectedTypeStr = self::errorTypeToString($expectedType);
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
				Assert::fail("$expectedTypeStr with a message matching '$expectedMessage' was expected but got " . Dumper::toLine($message), $expectedMessage, $message);
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
	 * Failed assertion
	 * @return void
	 */
	public static function fail($message, $expected = NULL, $actual = NULL)
	{
		call_user_func(self::$onFailure, $message, $expected, $actual);
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
			$arr1 = array_keys($expected);
			sort($arr1);
			$arr2 = array_keys($actual);
			sort($arr2);
			if ($arr1 !== $arr2) {
				return FALSE;
			}

			foreach ($expected as $key => $value) {
				if (!self::isEqual($value, $actual[$key], $level + 1)) {
					return FALSE;
				}
			}
			return TRUE;
		}
		return $expected === $actual;
	}


	/**
	 * Compares results using mask:
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
	 *   %ns%   PHP namespace
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public static function match($pattern, $actual)
	{
		if (!is_string($pattern)) {
			throw new \Exception('Pattern must be a string.');

		} elseif (!is_scalar($actual) || !self::isMatching($pattern, $actual)) {
			self::fail(Dumper::toLine($actual) . ' should match ' . Dumper::toLine($pattern), $pattern, $actual);
		}
	}


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

		$pattern = str_replace("\r\n", "\n", $pattern);
		$actual = str_replace("\r\n", "\n", $actual);
		$pattern = preg_replace("#[\t ]*\n#", "%s?%\n", rtrim($pattern));
		$re = strtr($pattern, array(
			'%a%' => '[^\r\n]+',    // one or more of anything except the end of line characters
			'%a?%'=> '[^\r\n]*',    // zero or more of anything except the end of line characters
			'%A%' => '.+',          // one or more of anything including the end of line characters
			'%A?%'=> '.*',          // zero or more of anything including the end of line characters
			'%s%' => '[\t ]+',      // one or more white space characters except the end of line characters
			'%s?%'=> '[\t ]*',      // zero or more white space characters except the end of line characters
			'%S%' => '\S+',         // one or more of characters except the white space
			'%S?%'=> '\S*',         // zero or more of characters except the white space
			'%c%' => '[^\r\n]',     // a single character of any sort (except the end of line)
			'%d%' => '[0-9]+',      // one or more digits
			'%d?%'=> '[0-9]*',      // zero or more digits
			'%i%' => '[+-]?[0-9]+', // signed integer value
			'%f%' => '[+-]?\.?\d+\.?\d*(?:[Ee][+-]?\d+)?', // floating point number
			'%h%' => '[0-9a-fA-F]+',// one or more HEX digits
			'%ns%'=> '(?:[_0-9a-zA-Z\\\\]+\\\\|N)?',// PHP namespace
			'%ds%'=> '[\\\\/]',     // directory separator

			'.' => '\.', '\\' => '\\\\', '+' => '\+', '*' => '\*', '?' => '\?', '[' => '\[', '^' => '\^', // preg quote
			']' => '\]', '$' => '\$', '(' => '\(', ')' => '\)', '{' => '\{', '}' => '\}', '=' => '\=', '!' => '\!',
			'>' => '\>', '<' => '\<', '|' => '\|', ':' => '\:', '-' => '\-', "\x00" => '\000', '#' => '\#',
		)) . '\\s*';

		$old = ini_set('pcre.backtrack_limit', '10000000');
		$res = preg_match("#^$re$#sU", $actual);
		ini_set('pcre.backtrack_limit', $old);
		if ($res === FALSE || preg_last_error()) {
			throw new \Exception("Error while executing regular expression. (PREG Error Code " . preg_last_error() . ")");
		}
		return (bool) $res;
	}


	/**
	 * Logs big variables to file and throws exception.
	 * @return void
	 */
	private static function assertionFailed($message, $expected, $actual)
	{
		$exception = new AssertException($message);
		$path = NULL;
		foreach ($exception->getTrace() as $item) {
			// in case of shutdown handler, we want to skip inner-code blocks and debugging calls
			$path = isset($item['file']) && substr($item['file'], -5) === '.phpt' ? $item['file'] : $path;
		}

		if ($path) {
			$path = dirname($path) . '/output/' . basename($path, '.phpt');
			if (isset($_SERVER['argv'][1])) {
				$path .= '.[' . preg_replace('#[^a-z0-9-. ]+#i', '_', $_SERVER['argv'][1]) . ']';
			}

			if (is_object($expected) || is_array($expected) || (is_string($expected) && strlen($expected) > 80)) {
				@mkdir(dirname($path)); // @ - directory may already exist
				file_put_contents($path . '.expected', is_string($expected) ? $expected : Dumper::toPhp($expected));
			}

			if (is_object($actual) || is_array($actual) || (is_string($actual) && strlen($actual) > 80)) {
				@mkdir(dirname($path)); // @ - directory may already exist
				file_put_contents($path . '.actual', is_string($actual) ? $actual : Dumper::toPhp($actual));
			}
		}

		throw $exception;
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
}
