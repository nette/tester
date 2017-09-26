<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester;


/**
 * Dumps PHP variables.
 */
class Dumper
{
	public static $maxLength = 70;

	public static $maxDepth = 10;

	public static $dumpDir = 'output';

	public static $maxPathSegments = 3;


	/**
	 * Dumps information about a variable in readable format.
	 * @param  mixed  variable to dump
	 * @return string
	 */
	public static function toLine($var)
	{
		static $table;
		if ($table === null) {
			foreach (array_merge(range("\x00", "\x1F"), range("\x7F", "\xFF")) as $ch) {
				$table[$ch] = '\x' . str_pad(dechex(ord($ch)), 2, '0', STR_PAD_LEFT);
			}
			$table['\\'] = '\\\\';
			$table["\r"] = '\r';
			$table["\n"] = '\n';
			$table["\t"] = '\t';
		}

		if (is_bool($var)) {
			return $var ? 'TRUE' : 'FALSE';

		} elseif ($var === null) {
			return 'NULL';

		} elseif (is_int($var)) {
			return "$var";

		} elseif (is_float($var)) {
			if (!is_finite($var)) {
				return str_replace('.0', '', var_export($var, true)); // workaround for PHP 7.0.2
			}
			$var = str_replace(',', '.', "$var");
			return strpos($var, '.') === false ? $var . '.0' : $var; // workaround for PHP < 7.0.2

		} elseif (is_string($var)) {
			if (preg_match('#^(.{' . self::$maxLength . '}).#su', $var, $m)) {
				$var = "$m[1]...";
			} elseif (strlen($var) > self::$maxLength) {
				$var = substr($var, 0, self::$maxLength) . '...';
			}
			return preg_match('#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u', $var) || preg_last_error() ? '"' . strtr($var, $table) . '"' : "'$var'";

		} elseif (is_array($var)) {
			$out = '';
			$counter = 0;
			foreach ($var as $k => &$v) {
				$out .= ($out === '' ? '' : ', ');
				if (strlen($out) > self::$maxLength) {
					$out .= '...';
					break;
				}
				$out .= ($k === $counter ? '' : self::toLine($k) . ' => ')
					. (is_array($v) && $v ? '[...]' : self::toLine($v));
				$counter = is_int($k) ? max($k + 1, $counter) : $counter;
			}
			return "[$out]";

		} elseif ($var instanceof \Exception || $var instanceof \Throwable) {
			return 'Exception ' . get_class($var) . ': ' . ($var->getCode() ? '#' . $var->getCode() . ' ' : '') . $var->getMessage();

		} elseif (is_object($var)) {
			return self::objectToLine($var);

		} elseif (is_resource($var)) {
			return 'resource(' . get_resource_type($var) . ')';

		} else {
			return 'unknown type';
		}
	}


	/**
	 * Formats object to line.
	 * @param  object
	 * @return string
	 */
	private static function objectToLine($object)
	{
		$line = get_class($object);
		if ($object instanceof \DateTime || $object instanceof \DateTimeInterface) {
			$line .= '(' . $object->format('Y-m-d H:i:s O') . ')';
		}

		return $line . '(' . self::hash($object) . ')';
	}


	/**
	 * Dumps variable in PHP format.
	 * @param  mixed  variable to dump
	 * @return string
	 */
	public static function toPhp($var)
	{
		return self::_toPhp($var);
	}


	/**
	 * Returns object's stripped hash.
	 * @param  object
	 * @return string
	 */
	private static function hash($object)
	{
		return '#' . substr(md5(spl_object_hash($object)), 0, 4);
	}


	/**
	 * @return string
	 */
	private static function _toPhp(&$var, &$list = [], $level = 0, &$line = 1)
	{
		if (is_float($var)) {
			$var = str_replace(',', '.', "$var");
			return strpos($var, '.') === false ? $var . '.0' : $var;

		} elseif (is_bool($var)) {
			return $var ? 'true' : 'false';

		} elseif ($var === null) {
			return 'null';

		} elseif (is_string($var) && (preg_match('#[^\x09\x20-\x7E\xA0-\x{10FFFF}]#u', $var) || preg_last_error())) {
			static $table;
			if ($table === null) {
				foreach (array_merge(range("\x00", "\x1F"), range("\x7F", "\xFF")) as $ch) {
					$table[$ch] = '\x' . str_pad(dechex(ord($ch)), 2, '0', STR_PAD_LEFT);
				}
				$table['\\'] = '\\\\';
				$table["\r"] = '\r';
				$table["\n"] = '\n';
				$table["\t"] = '\t';
				$table['$'] = '\$';
				$table['"'] = '\"';
			}
			return '"' . strtr($var, $table) . '"';

		} elseif (is_array($var)) {
			$space = str_repeat("\t", $level);

			static $marker;
			if ($marker === null) {
				$marker = uniqid("\x00", true);
			}
			if (empty($var)) {
				$out = '';

			} elseif ($level > self::$maxDepth || isset($var[$marker])) {
				return '/* Nesting level too deep or recursive dependency */';

			} else {
				$out = "\n$space";
				$outShort = '';
				$var[$marker] = true;
				$oldLine = $line;
				$line++;
				$counter = 0;
				foreach ($var as $k => &$v) {
					if ($k !== $marker) {
						$item = ($k === $counter ? '' : self::_toPhp($k, $list, $level + 1, $line) . ' => ') . self::_toPhp($v, $list, $level + 1, $line);
						$counter = is_int($k) ? max($k + 1, $counter) : $counter;
						$outShort .= ($outShort === '' ? '' : ', ') . $item;
						$out .= "\t$item,\n$space";
						$line++;
					}
				}
				unset($var[$marker]);
				if (strpos($outShort, "\n") === false && strlen($outShort) < self::$maxLength) {
					$line = $oldLine;
					$out = $outShort;
				}
			}
			return '[' . $out . ']';

		} elseif ($var instanceof \Closure) {
			$rc = new \ReflectionFunction($var);
			return "/* Closure defined in file {$rc->getFileName()} on line {$rc->getStartLine()} */";

		} elseif (is_object($var)) {
			if (PHP_VERSION_ID >= 70000 && ($rc = new \ReflectionObject($var)) && $rc->isAnonymous()) {
				return "/* Anonymous class defined in file {$rc->getFileName()} on line {$rc->getStartLine()} */";
			}
			$arr = (array) $var;
			$space = str_repeat("\t", $level);
			$class = get_class($var);
			$used = &$list[spl_object_hash($var)];

			if (empty($arr)) {
				$out = '';

			} elseif ($used) {
				return "/* $class dumped on line $used */";

			} elseif ($level > self::$maxDepth) {
				return '/* Nesting level too deep */';

			} else {
				$out = "\n";
				$used = $line;
				$line++;
				foreach ($arr as $k => &$v) {
					if ($k[0] === "\x00") {
						$k = substr($k, strrpos($k, "\x00") + 1);
					}
					$out .= "$space\t" . self::_toPhp($k, $list, $level + 1, $line) . ' => ' . self::_toPhp($v, $list, $level + 1, $line) . ",\n";
					$line++;
				}
				$out .= $space;
			}
			$hash = self::hash($var);
			return $class === 'stdClass'
				? "(object) /* $hash */ [$out]"
				: "$class::__set_state(/* $hash */ [$out])";

		} elseif (is_resource($var)) {
			return '/* resource ' . get_resource_type($var) . ' */';

		} else {
			$res = var_export($var, true);
			$line += substr_count($res, "\n");
			return $res;
		}
	}


	/**
	 * @param  \Exception|\Throwable
	 * @internal
	 */
	public static function dumpException($e)
	{
		$trace = $e->getTrace();
		array_splice($trace, 0, $e instanceof \ErrorException ? 1 : 0, [['file' => $e->getFile(), 'line' => $e->getLine()]]);

		$testFile = null;
		foreach (array_reverse($trace) as $item) {
			if (isset($item['file'])) { // in case of shutdown handler, we want to skip inner-code blocks and debugging calls
				$testFile = $item['file'];
				break;
			}
		}

		if ($e instanceof AssertException) {
			$expected = $e->expected;
			$actual = $e->actual;

			if (is_object($expected) || is_array($expected) || (is_string($expected) && strlen($expected) > self::$maxLength)
				|| is_object($actual) || is_array($actual) || (is_string($actual) && strlen($actual) > self::$maxLength)
			) {
				$args = isset($_SERVER['argv'][1])
					? '.[' . implode(' ', preg_replace(['#^-*(.{1,20}).*#i', '#[^=a-z0-9. -]+#i'], ['$1', '-'], array_slice($_SERVER['argv'], 1))) . ']'
					: '';
				$stored[] = self::saveOutput($testFile, $expected, $args . '.expected');
				$stored[] = self::saveOutput($testFile, $actual, $args . '.actual');
			}

			if ((is_string($actual) && is_string($expected))) {
				for ($i = 0; $i < strlen($actual) && isset($expected[$i]) && $actual[$i] === $expected[$i]; $i++);
				for (; $i && $i < strlen($actual) && $actual[$i - 1] >= "\x80" && $actual[$i] >= "\x80" && $actual[$i] < "\xC0"; $i--);
				$i = max(0, min(
					$i - (int) (self::$maxLength / 3), // try to display 1/3 of shorter string
					max(strlen($actual), strlen($expected)) - self::$maxLength + 3 // 3 = length of ...
				));
				if ($i) {
					$expected = substr_replace($expected, '...', 0, $i);
					$actual = substr_replace($actual, '...', 0, $i);
				}
			}

			$message = 'Failed: ' . $e->origMessage;
			if (((is_string($actual) && is_string($expected)) || (is_array($actual) && is_array($expected)))
				&& preg_match('#^(.*)(%\d)(.*)(%\d.*)\z#s', $message, $m)
			) {
				if (($delta = strlen($m[1]) - strlen($m[3])) >= 3) {
					$message = "$m[1]$m[2]\n" . str_repeat(' ', $delta - 3) . "...$m[3]$m[4]";
				} else {
					$message = "$m[1]$m[2]$m[3]\n" . str_repeat(' ', strlen($m[1]) - 4) . "... $m[4]";
				}
			}
			$message = strtr($message, [
				'%1' => self::color('yellow') . self::toLine($actual) . self::color('white'),
				'%2' => self::color('yellow') . self::toLine($expected) . self::color('white'),
			]);
		} else {
			$message = ($e instanceof \ErrorException ? Helpers::errorTypeToString($e->getSeverity()) : get_class($e))
				. ': ' . preg_replace('#[\x00-\x09\x0B-\x1F]+#', ' ', $e->getMessage());
		}

		$s = self::color('white', $message) . "\n\n"
			. (isset($stored) ? 'diff ' . Helpers::escapeArg($stored[0]) . ' ' . Helpers::escapeArg($stored[1]) . "\n\n" : '');

		foreach ($trace as $item) {
			$item += ['file' => null, 'class' => null, 'type' => null, 'function' => null];
			if ($e instanceof AssertException && $item['file'] === __DIR__ . DIRECTORY_SEPARATOR . 'Assert.php') {
				continue;
			}
			$line = $item['class'] === 'Tester\Assert' && method_exists($item['class'], $item['function'])
				&& strpos($tmp = file($item['file'])[$item['line'] - 1], "::$item[function](") ? $tmp : null;

			$s .= 'in '
				. ($item['file']
					? (
						($item['file'] === $testFile ? self::color('white') : '')
						. implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $item['file']), -self::$maxPathSegments))
						. "($item[line])" . self::color('gray') . ' '
					)
					: '[internal function]'
				)
				. ($line
					? trim($line)
					: $item['class'] . $item['type'] . $item['function'] . ($item['function'] ? '()' : '')
				)
				. self::color() . "\n";
		}

		if ($e->getPrevious()) {
			$s .= "\n(previous) " . static::dumpException($e->getPrevious());
		}
		return $s;
	}


	/**
	 * Dumps data to folder 'output'.
	 * @return string
	 * @internal
	 */
	public static function saveOutput($testFile, $content, $suffix = '')
	{
		$path = self::$dumpDir . DIRECTORY_SEPARATOR . pathinfo($testFile, PATHINFO_FILENAME) . $suffix;
		if (!preg_match('#/|\w:#A', self::$dumpDir)) {
			$path = dirname($testFile) . DIRECTORY_SEPARATOR . $path;
		}
		@mkdir(dirname($path)); // @ - directory may already exist
		file_put_contents($path, is_string($content) ? $content : (self::toPhp($content) . "\n"));
		return $path;
	}


	/**
	 * Applies color to string.
	 * @return string
	 */
	public static function color($color = '', $s = null)
	{
		static $colors = [
			'black' => '0;30', 'gray' => '1;30', 'silver' => '0;37', 'white' => '1;37',
			'navy' => '0;34', 'blue' => '1;34', 'green' => '0;32', 'lime' => '1;32',
			'teal' => '0;36', 'aqua' => '1;36', 'maroon' => '0;31', 'red' => '1;31',
			'purple' => '0;35', 'fuchsia' => '1;35', 'olive' => '0;33', 'yellow' => '1;33',
			null => '0',
		];
		$c = explode('/', $color);
		return "\e["
			. str_replace(';', "m\e[", $colors[$c[0]] . (empty($c[1]) ? '' : ';4' . substr($colors[$c[1]], -1)))
			. 'm' . $s . ($s === null ? '' : "\e[0m");
	}


	public static function removeColors($s)
	{
		return preg_replace('#\e\[[\d;]+m#', '', $s);
	}
}
