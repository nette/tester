<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester;

use const PHP_SAPI, STDOUT, STR_PAD_BOTH, STR_PAD_LEFT, STR_PAD_RIGHT;


/**
 * Terminal/console utilities for ANSI output.
 * @internal
 */
class Console
{
	/** ANSI sequence to clear from cursor to end of line */
	public const ClearLine = "\e[K";

	/** ANSI sequence to reset all attributes */
	public const Reset = "\e[0m";

	/** ANSI sequence for bold text */
	public const BoldOn = "\e[1m";

	/** ANSI sequence to turn off bold */
	public const BoldOff = "\e[22m";

	private const Colors = [
		'black' => '0;30', 'gray' => '1;30', 'silver' => '0;37', 'white' => '1;37',
		'navy' => '0;34', 'blue' => '1;34', 'green' => '0;32', 'lime' => '1;32',
		'teal' => '0;36', 'aqua' => '1;36', 'maroon' => '0;31', 'red' => '1;31',
		'purple' => '0;35', 'fuchsia' => '1;35', 'olive' => '0;33', 'yellow' => '1;33',
		'' => '0',
	];


	/**
	 * Detects whether the terminal supports colored output.
	 * Respects NO_COLOR standard and FORCE_COLOR env variable.
	 */
	public static function supportsColors(): bool
	{
		return (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg')
			&& getenv('NO_COLOR') === false // https://no-color.org
			&& (getenv('FORCE_COLOR')
				|| (function_exists('sapi_windows_vt100_support')
					? sapi_windows_vt100_support(STDOUT)
					: @stream_isatty(STDOUT)) // @ may trigger error 'cannot cast a filtered stream on this system'
			);
	}


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
		return self::color($color) . $text . self::Reset;
	}


	/**
	 * Removes all ANSI escape sequences from string (colors, cursor control, etc.).
	 */
	public static function stripAnsi(string $text): string
	{
		return preg_replace('/\e\[[0-?]*[ -\/]*[@-~]|\e\][^\x07]*(\x07|\e\\\)/', '', $text);
	}


	/**
	 * Returns ANSI sequence to show or hide cursor.
	 */
	public static function showCursor(bool $visible): string
	{
		return $visible ? "\e[?25h" : "\e[?25l";
	}


	/**
	 * Returns ANSI sequence to move cursor up.
	 */
	public static function cursorUp(int $lines): string
	{
		return "\e[{$lines}A";
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
