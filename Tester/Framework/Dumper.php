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
 * Dumps PHP variables.
 *
 * @author     David Grudl
 */
class Dumper
{

	/**
	 * Dumps information about a variable in readable format.
	 * @param  mixed  variable to dump
	 * @return string
	 */
	public static function toLine($var)
	{
		static $table;
		if ($table === NULL) {
			foreach (array_merge(range("\x00", "\x1F"), range("\x7F", "\xFF")) as $ch) {
				$table[$ch] = '\x' . str_pad(dechex(ord($ch)), 2, '0', STR_PAD_LEFT);
			}
			$table["\\"] = '\\\\';
			$table["\r"] = '\r';
			$table["\n"] = '\n';
			$table["\t"] = '\t';
		}

		if (is_bool($var)) {
			return $var ? 'TRUE' : 'FALSE';

		} elseif ($var === NULL) {
			return 'NULL';

		} elseif (is_int($var)) {
			return "$var";

		} elseif (is_float($var)) {
			$var = var_export($var, TRUE);
			return strpos($var, '.') === FALSE ? $var . '.0' : $var;

		} elseif (is_string($var)) {
			if ($cut = @iconv_strlen($var, 'UTF-8') > 100) {
				$var = iconv_substr($var, 0, 100, 'UTF-8');
			} elseif ($cut = strlen($var) > 100) {
				$var = substr($var, 0, 100);
			}
			return (preg_match('#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u', $var) || preg_last_error() ? '"' . strtr($var, $table) . '"' : "'$var'")
				. ($cut ? ' ...' : '');

		} elseif (is_array($var)) {
			return 'array(' . count($var) . ')';

		} elseif ($var instanceof \Exception) {
			return 'Exception ' . get_class($var) . ': ' . ($var->getCode() ? '#' . $var->getCode() . ' ' : '') . $var->getMessage();

		} elseif (is_object($var)) {
			return get_class($var) . '(' . count((array) $var) . ')';

		} elseif (is_resource($var)) {
			return 'resource(' . get_resource_type($var) . ')';

		} else {
			return 'unknown type';
		}
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
	 * @return string
	 */
	private static function _toPhp(&$var, $level = 0)
	{
		if (is_float($var)) {
			$var = var_export($var, TRUE);
			return strpos($var, '.') === FALSE ? $var . '.0' : $var;

		} elseif (is_bool($var)) {
			return $var ? 'TRUE' : 'FALSE';

		} elseif (is_string($var) && (preg_match('#[^\x09\x20-\x7E\xA0-\x{10FFFF}]#u', $var) || preg_last_error())) {
			static $table;
			if ($table === NULL) {
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
			if ($marker === NULL) {
				$marker = uniqid("\x00", TRUE);
			}
			if (empty($var)) {
				$out = '';

			} elseif ($level > 50 || isset($var[$marker])) {
				return '/* Nesting level too deep or recursive dependency */';

			} else {
				$out = '';
				$outAlt = "\n$space";
				$var[$marker] = TRUE;
				$counter = 0;
				foreach ($var as $k => &$v) {
					if ($k !== $marker) {
						$item = ($k === $counter ? '' : self::_toPhp($k, $level + 1) . ' => ') . self::_toPhp($v, $level + 1);
						$counter = is_int($k) ? max($k + 1, $counter) : $counter;
						$out .= ($out === '' ? '' : ', ') . $item;
						$outAlt .= "\t$item,\n$space";
					}
				}
				unset($var[$marker]);
			}
			return 'array(' . (strpos($out, "\n") === FALSE && strlen($out) < 40 ? $out : $outAlt) . ')';

		} elseif (is_object($var)) {
			$arr = (array) $var;
			$space = str_repeat("\t", $level);

			static $list = array();
			if (empty($arr)) {
				$out = '';

			} elseif ($level > 50 || in_array($var, $list, TRUE)) {
				return '/* Nesting level too deep or recursive dependency */';

			} else {
				$out = "\n";
				$list[] = $var;
				foreach ($arr as $k => &$v) {
					if ($k[0] === "\x00") {
						$k = substr($k, strrpos($k, "\x00") + 1);
					}
					$out .= "$space\t" . self::_toPhp($k, $level + 1) . ' => ' . self::_toPhp($v, $level + 1) . ",\n";
				}
				array_pop($list);
				$out .= $space;
			}
			return get_class($var) === 'stdClass'
				? "(object) array($out)"
				: get_class($var) . "::__set_state(array($out))";

		} elseif (is_resource($var)) {
			return '/* resource ' . get_resource_type($var) . ' */';

		} else {
			return var_export($var, TRUE);
		}
	}

}
