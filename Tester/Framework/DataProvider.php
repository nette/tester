<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester;


/**
 * Data provider helpers.
 *
 * @author     David Grudl
 */
class DataProvider
{

	/**
	 * Loads data according to the file annotation or specified by Tester\Runner\TestHandler::initiateDataProvider()
	 * @return array
	 */
	public static function loadCurrent()
	{
		if (isset($_SERVER['argv'][2])) {
			list(, $query, $file) = $_SERVER['argv'];

		} else {
			$trace = debug_backtrace();
			$file = $trace[count($trace) - 1]['file'];
			$annotations = Helpers::parseDocComment(file_get_contents($file));
			if (!isset($annotations['dataprovider'])) {
				throw new \Exception('Missing annotation @dataProvider.');
			}
			$provider = (array) $annotations['dataprovider'];
			list($file, $query) = self::parseAnnotation($provider[0], $file);
		}
		$data = self::load($file, $query);
		return reset($data);
	}


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


	/**
	 * @return [file, query, optional]
	 * @internal
	 */
	public static function parseAnnotation($annotation, $file)
	{
		if (!preg_match('#^(\??)\s*([^,\s]+)\s*,?\s*(\S.*)?()#', $annotation, $m)) {
			throw new \Exception("Invalid @dataProvider value '$annotation'.");
		}
		return array(dirname($file) . DIRECTORY_SEPARATOR . $m[2], $m[3], (bool) $m[1]);
	}

}
