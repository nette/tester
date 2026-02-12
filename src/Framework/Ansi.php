<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester;


/**
 * ANSI escape sequences for terminal colors, cursor control, and text formatting.
 * @internal
 */
class Ansi
{
	private const Colors = [
		'black' => '0;30', 'gray' => '1;30', 'silver' => '0;37', 'white' => '1;37',
		'navy' => '0;34', 'blue' => '1;34', 'green' => '0;32', 'lime' => '1;32',
		'teal' => '0;36', 'aqua' => '1;36', 'maroon' => '0;31', 'red' => '1;31',
		'purple' => '0;35', 'fuchsia' => '1;35', 'olive' => '0;33', 'yellow' => '1;33',
		'' => '0',
	];


	/**
	 * Returns ANSI escape sequence for given color.
	 * Color format: 'foreground' or 'foreground/background' (e.g. 'red', 'white/blue').
	 */
	public static function color(string $color): string
	{
		$c = explode('/', $color);
		return "\e["
			. str_replace(';', "m\e[", self::Colors[$c[0]] . (empty($c[1]) ? '' : ';4' . substr(self::Colors[$c[1]], -1)))
			. 'm';
	}


	/**
	 * Wraps text with ANSI color sequence and color reset.
	 * Color format: 'foreground' or 'foreground/background' (e.g. 'red', 'white/blue').
	 */
	public static function colorize(string $text, string $color): string
	{
		return self::color($color) . $text . self::reset();
	}


	/**
	 * Returns ANSI sequence to turn on bold text.
	 */
	public static function boldOn(): string
	{
		return "\e[1m";
	}


	/**
	 * Returns ANSI sequence to turn off bold text.
	 */
	public static function boldOff(): string
	{
		return "\e[22m";
	}


	public static function showCursor(): string
	{
		return "\e[?25h";
	}


	public static function hideCursor(): string
	{
		return "\e[?25l";
	}


	public static function cursorMove(int $x = 0, int $y = 0): string
	{
		return match (true) {
			$x < 0 => "\e[" . (-$x) . 'D',
			$x > 0 => "\e[{$x}C",
			default => '',
		} . match (true) {
			$y < 0 => "\e[" . (-$y) . 'A',
			$y > 0 => "\e[{$y}B",
			default => '',
		};
	}


	/**
	 * Returns ANSI sequence to clear from cursor to end of line.
	 */
	public static function clearLine(): string
	{
		return "\e[K";
	}


	/**
	 * Removes all ANSI escape sequences from string (colors, cursor control, etc.).
	 */
	public static function stripAnsi(string $text): string
	{
		return preg_replace('/\e\[[0-?]*[ -\/]*[@-~]|\e\][^\x07]*(\x07|\e\\\)/', '', $text);
	}


	/**
	 * Returns ANSI sequence to reset all attributes.
	 */
	public static function reset(): string
	{
		return "\e[0m";
	}


	/**
	 * Returns display width of string (number of terminal columns).
	 */
	public static function textWidth(string $text): int
	{
		$text = self::stripAnsi($text);
		return preg_match_all('/./su', $text)
			+ preg_match_all('/[\x{1F300}-\x{1F9FF}]/u', $text); // emoji are 2-wide
	}


	/**
	 * Pads text to specified display width.
	 * @param STR_PAD_LEFT|STR_PAD_RIGHT|STR_PAD_BOTH  $type
	 */
	public static function pad(
		string $text,
		int $width,
		string $char = ' ',
		int $type = STR_PAD_RIGHT,
	): string
	{
		$padding = $width - self::textWidth($text);
		if ($padding <= 0) {
			return $text;
		}

		return match ($type) {
			STR_PAD_LEFT => str_repeat($char, $padding) . $text,
			STR_PAD_RIGHT => $text . str_repeat($char, $padding),
			STR_PAD_BOTH => str_repeat($char, intdiv($padding, 2)) . $text . str_repeat($char, $padding - intdiv($padding, 2)),
		};
	}


	/**
	 * Truncates text to max display width, adding ellipsis if needed.
	 */
	public static function truncate(string $text, int $maxWidth, string $ellipsis = '…'): string
	{
		if (self::textWidth($text) <= $maxWidth) {
			return $text;
		}

		$maxWidth -= self::textWidth($ellipsis);
		$res = '';
		$width = 0;
		foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) as $char) {
			$charWidth = preg_match('/[\x{1F300}-\x{1F9FF}]/u', $char) ? 2 : 1;
			if ($width + $charWidth > $maxWidth) {
				break;
			}
			$res .= $char;
			$width += $charWidth;
		}

		return $res . $ellipsis;
	}
}
