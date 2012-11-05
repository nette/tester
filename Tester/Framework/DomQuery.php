<?php

/**
 * This file is part of the Nette Tester.
 *
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */



/**
 * DomQuery simplifies querying (X)HTML documents.
 *
 * @author     David Grudl
 */
class DomQuery extends SimpleXMLElement
{

	/**
	 * @return DomQuery
	 */
	public static function fromHtml($html)
	{
		if (strpos($html, '<') === FALSE) {
			$html = '<body>' . $html;
		}
		$dom = new \DOMDocument();
		$dom->loadHTML($html);
		return simplexml_import_dom($dom, __CLASS__);
	}



	/**
	 * Returns array of descendants filtered by a selector.
	 * @return DomQuery[]
	 */
	public function find($selector)
	{
		return $this->xpath(self::css2xpath($selector));
	}



	/**
	 * Check the current document against a selector.
	 * @return bool
	 */
	public function has($selector)
	{
		return (bool) $this->find($selector);
	}



	/**
	 * Transforms CSS expression to XPath.
	 * @return string
	 */
	public static function css2xpath($css)
	{
		$xpath = '//*';
		preg_match_all('/([#.:]?)([a-z][a-z0-9_-]*)|\[([a-z0-9_-]+)(?:([~*^$]?)=([^\]]+))?\]|\s*([>,+~])\s*|(\s+)|(\*)/i', trim($css), $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			if ($m[1] === '#') { // #ID
				$xpath .= "[@id='$m[2]']";
			} elseif ($m[1] === '.') { // .class
				$xpath .= "[contains(concat(' ', normalize-space(@class), ' '), ' $m[2] ')]";
			} elseif ($m[1] === ':') { // :pseudo-class
				throw new InvalidArgumentException('Not implemented.');
			} elseif ($m[2]) { // tag
				$xpath = rtrim($xpath, '*') . $m[2];
			} elseif ($m[3]) { // [attribute]
				$attr = '@' . strtolower($m[3]);
				if (!isset($m[5])) {
					$xpath .= "[$attr]";
					continue;
				}
				$val = trim($m[5], '"\'');
				if ($m[4] === '') {
					$xpath .= "[$attr='$val']";
				} elseif ($m[4] === '~') {
					$xpath .= "[contains(concat(' ', normalize-space($attr), ' '), ' $val ')]";
				} elseif ($m[4] === '*') {
					$xpath .= "[contains($attr, '$val')]";
				} elseif ($m[4] === '^') {
					$xpath .= "[starts-with($attr, '$val')]";
				} elseif ($m[4] === '$') {
					$xpath .= "[substring($attr, string-length($attr)-0)='$val']";
				}
			} elseif ($m[6] === '>') {
				$xpath .= '/*';
			} elseif ($m[6] === ',') {
				$xpath .= '|//*';
			} elseif ($m[6] === '~') {
				$xpath .= '/following-sibling::*';
			} elseif ($m[6] === '+') {
				throw new InvalidArgumentException('Not implemented.');
			} elseif ($m[7]) {
				$xpath .= '//*';
			}
		}
		return $xpath;
	}

}
