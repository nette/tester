<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester;


/**
 * Mock files.
 */
class FileMock
{
	const PROTOCOL = 'mock';

	/** @var string[] */
	public static $files = array();

	/** @var string */
	private $content;

	/** @var int */
	private $pos;


	/**
	 * @return string  file name
	 */
	public static function create($content, $extension = NULL)
	{
		if (!self::$files) {
			stream_wrapper_register(self::PROTOCOL, __CLASS__);
		}
		static $id;
		$name = self::PROTOCOL . '://' . (++$id) . '.' . $extension;
		self::$files[$name] = $content;
		return $name;
	}


	public function stream_open($path, $mode)
	{
		$this->content = & self::$files[$path];
		$this->pos = strpos($mode, 'a') === FALSE ? 0 : strlen($this->content);
		return TRUE;
	}


	public function stream_read($len)
	{
		$res = substr($this->content, $this->pos, $len);
		$this->pos += strlen($res);
		return $res;
	}


	public function stream_write($data)
	{
		$this->content = substr($this->content, 0, $this->pos)
			. str_repeat("\x00", max(0, $this->pos - strlen($this->content)))
			. $data
			. substr($this->content, $this->pos + strlen($data));
		$this->pos += strlen($data);
		return strlen($data);
	}


	public function stream_tell()
	{
		return $this->pos;
	}


	public function stream_eof()
	{
		return $this->pos >= strlen($this->content);
	}


	public function stream_seek($offset, $whence)
	{
		if ($whence === SEEK_CUR) {
			$offset += $this->pos;
		} elseif ($whence === SEEK_END) {
			$offset += strlen($this->content);
		}
		if ($offset >= 0) {
			$this->pos = $offset;
			return TRUE;
		} else {
			return FALSE;
		}
	}


	public function stream_truncate($size)
	{
		$this->content = (string) substr($this->content, 0, $size)
			. str_repeat("\x00", max(0, $size - strlen($this->content)));
		return TRUE;
	}


	public function stream_stat()
	{
		return array('mode' => 0100666, 'size' => strlen($this->content));
	}


	public function url_stat($path, $flags)
	{
		return isset(self::$files[$path])
			? array('mode' => 0100666, 'size' => strlen(self::$files[$path]))
			: FALSE;
	}

}
