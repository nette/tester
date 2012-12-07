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
 * Data provider helpers.
 *
 * @author     David Grudl
 */
class DataProvider
{


	public static function load($file, $query = NULL)
	{
		if (!is_file($file)) {
			throw new \Exception("Missing data-provider file '$file'.");
		}

		$data = @parse_ini_file($file, TRUE);
		if ($data === FALSE) {
			throw new \Exception("Cannot parse data-provider file '$file'.");
		}

		foreach ($data as $key => $value) {
			if (!self::testQuery($key, $query)) {
				unset($data[$key]);
			}
		}

		if (!$data) {
			throw new \Exception("No records in data-provider file '$file'" . ($query ? " for query '$query'" : '') . '.');
		}
		return $data;
	}



	public static function testQuery($input, $query)
	{
		static $replaces = array('' => '=', '=>' => '>=', '=<' => '<=');
		$tokens = preg_split('#\s+#', $input);
		preg_match_all('#\s*,?\s*(<=|=<|<|==|=|!=|<>|>=|=>|>)?\s*([^\s,]+)#A', $query, $queryParts, PREG_SET_ORDER);
		foreach ($queryParts as $queryPart) {
			list(, $operator, $operand) = $queryPart;
			$operator = isset($replaces[$operator]) ? $replaces[$operator] : $operator;
			$token = array_shift($tokens);
			$res = preg_match('#^[0-9.]+\z#', $token)
				? version_compare($token, $operand, $operator)
				: self::compare($token, $operator, $operand);
			if (!$res) {
				return FALSE;
			}
		}
		return TRUE;
	}



	private static function compare($l, $operator, $r)
	{
		switch ($operator) {
		case '>':
			return $l > $r;
		case '=>':
		case '>=':
			return $l >= $r;
		case '<':
			return $l < $r;
		case '=<':
		case '<=':
			return $l <= $r;
		case '=':
		case '==':
			return $l == $r;
		case '!':
		case '!=':
		case '<>':
			return $l != $r;
		}
		throw new \InvalidArgumentException("Unknown operator $operator.");
	}

}
