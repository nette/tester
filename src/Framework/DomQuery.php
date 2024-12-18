<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester;

use Dom;


/**
 * DomQuery simplifies querying (X)HTML documents.
 */
class DomQuery extends \SimpleXMLElement
{
	/**
	 * Creates a DomQuery object from an HTML string.
	 */
	public static function fromHtml(string $html): self
	{
		$old = libxml_use_internal_errors(true);
		libxml_clear_errors();

		if (PHP_VERSION_ID < 80400) {
			if (!str_contains($html, '<')) {
				$html = '<body>' . $html;
			}

			// parse these elements as void
			$html = preg_replace('#<(keygen|source|track|wbr)(?=\s|>)((?:"[^"]*"|\'[^\']*\'|[^"\'>])*+)(?<!/)>#', '<$1$2 />', $html);

			// fix parsing of </ inside scripts
			$html = preg_replace_callback(
				'#(<script(?=\s|>)(?:"[^"]*"|\'[^\']*\'|[^"\'>])*+>)(.*?)(</script>)#s',
				fn(array $m): string => $m[1] . str_replace('</', '<\/', $m[2]) . $m[3],
				$html,
			);

			$dom = new \DOMDocument;
			$dom->loadHTML($html);
		} else {
			if (!preg_match('~<!DOCTYPE~i', $html)) {
				$html = '<!DOCTYPE html>' . $html;
			}
			$dom = Dom\HTMLDocument::createFromString($html, Dom\HTML_NO_DEFAULT_NS);
		}

		$errors = libxml_get_errors();
		libxml_use_internal_errors($old);

		foreach ($errors as $error) {
			if (!preg_match('#Tag \S+ invalid#', $error->message)) {
				trigger_error(__METHOD__ . ": $error->message on line $error->line.", E_USER_WARNING);
			}
		}

		return simplexml_import_dom($dom, self::class);
	}


	/**
	 * Creates a DomQuery object from an XML string.
	 */
	public static function fromXml(string $xml): self
	{
		return simplexml_load_string($xml, self::class);
	}


	/**
	 * Finds descendants of current element that match the given CSS selector.
	 * @return DomQuery[]
	 */
	public function find(string $selector): array
	{
		$base = str_starts_with($selector, '>') ? 'self' : 'descendant';
		return $this->xpath($base . '::' . self::css2xpath($selector));
	}


	/**
	 * Checks if any descendant of current element matches the given selector.
	 */
	public function has(string $selector): bool
	{
		return (bool) $this->find($selector);
	}


	/**
	 * Determines if the current element matches the specified CSS selector.
	 */
	public function matches(string $selector): bool
	{
		return (bool) $this->xpath('self::' . self::css2xpath($selector));
	}


	/**
	 * Converts a CSS selector into an XPath expression.
	 */
	public static function css2xpath(string $css): string
	{
		$xpath = '*';
		preg_match_all(<<<'XX'
			/
				([#.:]?)([a-z][a-z0-9_-]*)|               # id, class, pseudoclass (1,2)
				\[
					([a-z0-9_-]+)
					(?:
						([~*^$]?)=(
							"[^"]*"|
							'[^']*'|
							[^\]]+
						)
					)?
				\]|                                       # [attr=val] (3,4,5)
				\s*([>,+~])\s*|                           # > , + ~ (6)
				(\s+)|                                    # whitespace (7)
				(\*)                                      # * (8)
			/ix
			XX, trim($css), $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			if ($m[1] === '#') { // #ID
				$xpath .= "[@id='$m[2]']";
			} elseif ($m[1] === '.') { // .class
				$xpath .= "[contains(concat(' ', normalize-space(@class), ' '), ' $m[2] ')]";
			} elseif ($m[1] === ':') { // :pseudo-class
				throw new \InvalidArgumentException('Not implemented.');
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
				throw new \InvalidArgumentException('Not implemented.');
			} elseif ($m[7]) {
				$xpath .= '//*';
			}
		}

		return $xpath;
	}
}
