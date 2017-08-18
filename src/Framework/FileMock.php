<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester;


/**
 * Mock files.
 */
class FileMock
{
	const PROTOCOL = 'mock';

	/** @var string[] */
	public static $files = [];

	/** @var string */
	private $content;

	/** @var int */
	private $readingPos;

	/** @var int */
	private $writingPos;

	/** @var bool */
	private $appendMode;

	/** @var bool */
	private $isReadable;

	/** @var bool */
	private $isWritable;


	/**
	 * @return string  file name
	 */
	public static function create($content = '', $extension = null)
	{
		self::register();

		static $id;
		$name = self::PROTOCOL . '://' . (++$id) . '.' . $extension;
		self::$files[$name] = $content;
		return $name;
	}


	public static function register()
	{
		if (!in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
			stream_wrapper_register(self::PROTOCOL, __CLASS__);
		}
	}


	public function stream_open($path, $mode)
	{
		if (!preg_match('#^([rwaxc]).*?(\+)?#', $mode, $m)) {
			// Windows: failed to open stream: Bad file descriptor
			// Linux: failed to open stream: Illegal seek
			$this->warning("failed to open stream: Invalid mode '$mode'");
			return false;

		} elseif ($m[1] === 'x' && isset(self::$files[$path])) {
			$this->warning('failed to open stream: File exists');
			return false;

		} elseif ($m[1] === 'r' && !isset(self::$files[$path])) {
			$this->warning('failed to open stream: No such file or directory');
			return false;

		} elseif ($m[1] === 'w' || $m[1] === 'x') {
			self::$files[$path] = '';
		}

		$this->content = &self::$files[$path];
		$this->content = (string) $this->content;
		$this->appendMode = $m[1] === 'a';
		$this->readingPos = 0;
		$this->writingPos = $this->appendMode ? strlen($this->content) : 0;
		$this->isReadable = isset($m[2]) || $m[1] === 'r';
		$this->isWritable = isset($m[2]) || $m[1] !== 'r';

		return true;
	}


	public function stream_read($length)
	{
		if (!$this->isReadable) {
			return '';
		}

		$result = substr($this->content, $this->readingPos, $length);
		$this->readingPos += strlen($result);
		$this->writingPos += $this->appendMode ? 0 : strlen($result);
		return $result;
	}


	public function stream_write($data)
	{
		if (!$this->isWritable) {
			return 0;
		}

		$length = strlen($data);
		$this->content = str_pad($this->content, $this->writingPos, "\x00");
		$this->content = substr_replace($this->content, $data, $this->writingPos, $length);
		$this->readingPos += $length;
		$this->writingPos += $length;
		return $length;
	}


	public function stream_tell()
	{
		return $this->readingPos;
	}


	public function stream_eof()
	{
		return $this->readingPos >= strlen($this->content);
	}


	public function stream_seek($offset, $whence)
	{
		if ($whence === SEEK_CUR) {
			$offset += $this->readingPos;
		} elseif ($whence === SEEK_END) {
			$offset += strlen($this->content);
		}
		if ($offset >= 0) {
			$this->readingPos = $offset;
			$this->writingPos = $this->appendMode ? $this->writingPos : $offset;
			return true;
		} else {
			return false;
		}
	}


	public function stream_truncate($size)
	{
		if (!$this->isWritable) {
			return false;
		}

		$this->content = substr(str_pad($this->content, $size, "\x00"), 0, $size);
		$this->writingPos = $this->appendMode ? $size : $this->writingPos;
		return true;
	}


	public function stream_stat()
	{
		return ['mode' => 0100666, 'size' => strlen($this->content)];
	}


	public function url_stat($path, $flags)
	{
		return isset(self::$files[$path])
			? ['mode' => 0100666, 'size' => strlen(self::$files[$path])]
			: false;
	}


	public function stream_lock($operation)
	{
		return false;
	}


	public function unlink($path)
	{
		if (isset(self::$files[$path])) {
			unset(self::$files[$path]);
			return true;
		}

		$this->warning('No such file');
		return false;
	}


	private function warning($message)
	{
		$bt = debug_backtrace(0, 3);
		if (isset($bt[2]['function'])) {
			$message = $bt[2]['function'] . '(' . @$bt[2]['args'][0] . '): ' . $message;
		}

		trigger_error($message, E_USER_WARNING);
	}
}
