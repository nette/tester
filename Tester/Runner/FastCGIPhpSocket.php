<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester\Environment;


/**
 * FastCGI PHP socket interpreter.
 *
 * @author     Miloslav Hůla
 */
class FastCGIPhpSocket implements IPhpInterpreter
{
	/** Version of FastCGI protocol */
	const FCGI_VERSION_1 = 1;

	/** [bytes] */
	const FCGI_HEADER_LEN = 8;

	/** Record types */
	const
		FCGI_BEGIN_REQUEST = 1,
		#FCGI_ABORT_REQUEST = 2,
		FCGI_END_REQUEST = 3,
		FCGI_PARAMS = 4,
		#FCGI_STDIN = 5,
		FCGI_STDOUT = 6,
		FCGI_STDERR = 7;
		#FCGI_DATA = 8,
		#FCGI_GET_VALUES = 9,
		#FCGI_GET_VALUES_RESULT = 10,
		#FCGI_UNKNOWN_TYPE = 11,
		#FCGI_MAXTYPE = self::UNKNOWN_TYPE,

		#NULL_REQUEST_ID = 0;

	/** Keep socket opened for next request flag. */
	const
		FCGI_KEEP_CONN = 1;

	/** Role of FastCGI server */
	const
		FCGI_RESPONDER = 1;
		#AUTHORIZER = 2,
		#FILTER = 3,

	/** Request end protocol status */
	const
		FCGI_REQUEST_COMPLETE = 0,
		FCGI_CANT_MPX_CONN = 1,
		FCGI_OVERLOADED = 2,
		FCGI_UNKNOWN_ROLE = 3;

		#MAX_CONNS = 'FCGI_MAX_CONNS',
		#MAX_REQS = 'FCGI_MAX_REQS',
		#MPXS_CONNS = 'FCGI_MPXS_CONNS';

	/** @internal */
	const REQUEST_ID = 1;


	/** @var string */
	private $domain;

	/** @var int */
	private $port;

	/** @var array */
	private $iniValues = array();

	/** @var resource */
	private $socket;

	/** @var string */
	private $stdout;

	/** @var string */
	private $buffer = '';

	/** @var stdClass */
	private $header;


	/**
	 * @param  string  path to test file
	 * @param  string  hostname or path to UNIX socket
	 * @param  int  TCP port
	 */
	public function __construct($domain, $port = NULL)
	{
		$this->domain = $domain;
		$this->port = $port;
	}


	/**
	 * @return string
	 */
	public function getShortInfo()
	{
		return "FastCGI on $this->domain" . ($this->port ? ":$this->port" : '');
	}


	/**
	 * @return string
	 */
	public function getVersion()
	{
		return '9.9.9'; /** @todo */
	}


	/**
	 * @return bool
	 */
	public function hasXdebug()
	{
		return FALSE; /** @todo */
	}


	/**
	 * @return bool
	 */
	public function isCgi()
	{
		return TRUE;
	}


	/**
	 * @param  string
	 * @param  string
	 */
	public function setIniValue($name, $value)
	{
		$this->iniValues[$name] = $value;
	}


	/**
	 * @inherit
	 */
	public function run($file, array $arguments, array $iniValues, array $envVars)
	{
		$this->socketOpen();

		$params = array(
			'GATEWAY_INTERFACE' => 'FastCGI/1.0',
			'REQUEST_METHOD' => 'GET',
			'SERVER_NAME' => php_uname('n'),
			'SCRIPT_FILENAME' => $file,
			Environment::FCGI => '1',
			Environment::FCGI_ARGS => serialize(array_merge(array(__FILE__), array_values($arguments))),  /** @todo  Merge?*/
			Environment::FCGI_INI => serialize(array_merge($this->iniValues, $iniValues)),
		);

		foreach ($envVars as $name => $value) {
			$params[$name] = $value;
		}

		/* // POST data
		$stdinLen = strlen($stdin);
		if ($stdinLen) {
			$params += array(
				'CONTENT_LENGTH' => $stdinLen,
			);
		}*/


		$raw = $this->encodeRecord(
			self::FCGI_BEGIN_REQUEST,
			self::REQUEST_ID,
			$this->encodeBeginRequestBody(self::FCGI_RESPONDER, $keepAlive = FALSE)
		);

		$raw .= $this->encodeRecord(self::FCGI_PARAMS, self::REQUEST_ID, $this->encodeNameValues($params));
		$raw .= $this->encodeRecord(self::FCGI_PARAMS, self::REQUEST_ID, '');

		/*if ($stdinLen) {
			$raw .= $this->encodeRecord(self::FCGI_STDIN, self::REQUEST_ID, $stdin);
			$raw .= $this->encodeRecord(self::FCGI_STDIN, self::REQUEST_ID, '');
		}*/

		$this->socketWrite($raw);
		$this->header = NULL;
	}


	/**
	 * @return bool
	 */
	public function isRunning()
	{
		if (!is_resource($this->socket)) {
			return FALSE;
		}

		if ($this->header === NULL) {
			try {
				$this->header = $this->readHeader();
			} catch (FastCGISocketShortReadException $e) {
				return TRUE;
			}
		}

		if ($this->header->id !== self::REQUEST_ID) {
			throw new FastCGIException("Request ID " . self::REQUEST_ID . " expected but got '{$this->header->id}'.");
		}


		try {
			$content = $this->socketRead($this->header->contentLength);
		} catch (FastCGISocketShortReadException $e) {
			return TRUE;
		}

		$padding = NULL;
		while ($padding === NULL) {
			try {
				$padding = $this->socketRead($this->header->paddingLength);
			} catch (FastCGISocketShortReadException $e) {
				usleep(10000); /** @todo Parallel performance. */
			}
		}


		if ($this->header->type === self::FCGI_STDOUT) {
			$this->stdout .= $content;

		} elseif ($this->header->type === self::FCGI_STDERR) {
			throw new \Exception('Unexpected stderr.'); /** @todo Drop silently? */

		} elseif ($this->header->type === self::FCGI_END_REQUEST) {
			$requestEnd = $this->decodeEndRequestBody($content);

			switch ($requestEnd->protocolStatus) {
				case self::FCGI_REQUEST_COMPLETE:
					break;

				case self::FCGI_CANT_MPX_CONN:
					throw new FastCGIException('FastCGI server cannot multiplex connections.');

				case self::FCGI_OVERLOADED:
					throw new FastCGIException('FastCGI server is overloaded.');

				case self::FCGI_UNKNOWN_ROLE:
					throw new FastCGIException('FastCGI server is not in RESPONDER role.');

				default:
					throw new FastCGIException("Unknown REQUEST_END protocol status: $requestEnd->protocolStatus");
			}

			fclose($this->socket);
			$this->socket = NULL;
			return FALSE;

		} else {
			throw new FastCGIException("Unexpected response type in header '{$this->header->type}'.");
		}

		$this->header = NULL;
		return TRUE;
	}


	/**
	 * @return array[int exitCode, string stdout]
	 */
	function getResult()
	{
		if (!preg_match('#^(.*)\nNETTE_TESTER_FCGI_EXIT_CODE:(\d+)\n#s', $this->stdout, $match)) {
			return array(Job::CODE_ERROR, 'Missing exit code in stdout.');
		}

		return array((int) $match[2], $match[1]);
	}



	/** FastCGI protocol implementation ***************************************/
	/**
	 * @param  int
	 * @param  int
	 * @param  string
	 * @return string
	 * @see http://www.fastcgi.com/devkit/doc/fcgi-spec.html#S3.3
	 */
	private function encodeRecord($type, $id, $content)
	{
		return pack('CCnnCC',
			self::FCGI_VERSION_1,
			$type,
			$id,
			strlen($content),
			0, # paddingLength
			0  # reserved
		) . $content;
	}


	/**
	 * @param  int
	 * @param  bool
	 * @return string
	 * @see http://www.fastcgi.com/devkit/doc/fcgi-spec.html#S5.1
	 */
	private function encodeBeginRequestBody($role, $keepAlive)
	{
		return pack('nC',
			$role,
			$keepAlive ? self::FCGI_KEEP_CONN : 0
		) . str_repeat("\x00", 5);
	}


	/**
	 * @param  string
	 * @param  string
	 * @return string
	 * @see http://www.fastcgi.com/devkit/doc/fcgi-spec.html#S3.4
	 */
	private function encodeNameValue($name, $value)
	{
		$value = (string) $value;

		$nLen = strlen($name);
		$vLen = strlen($value);

		return pack(($nLen < 128 ? 'C' : 'N') . ($vLen < 128 ? 'C' : 'N'),
			$nLen < 128 ? $nLen : (0x80000000 | $nLen),
			$vLen < 128 ? $vLen : (0x80000000 | $vLen)
		) . $name . $value;
	}


	/**
	 * @param  [name => value]
	 * @return string
	 */
	private function encodeNameValues(array $values)
	{
		$encoded = '';
		foreach ($values as $name => $value) {
			$encoded .= $this->encodeNameValue($name, $value);
		}
		return $encoded;
	}


	/**
	 * @return stdClass(version, type, id, contentLength, paddingLength)
	 */
	private function readHeader()
	{
		return (object) unpack(
			'Cversion/Ctype/nid/ncontentLength/CpaddingLength',
			$this->socketRead(self::FCGI_HEADER_LEN)
		);
	}


	/**
	 * @param  int
	 * @return array
	 */
	private function readNameValues($length)
	{
		$variables = array();

		$i = 0;
		$raw = $this->socketRead($length);
		while ($i < $length) {
			$nLen = ord($raw[$i++]);
			if ($nLen > 127) {
				$nLen = ($nLen & 0x7F) << 24;
				$nLen |= ord($raw[$i++]) << 16;
				$nLen |= ord($raw[$i++]) << 8;
				$nLen |= ord($raw[$i++]);
			}

			$vLen = ord($raw[$i++]);
			if ($vLen > 127) {
				$vLen = ($vLen & 0x7F) << 24;
				$vLen |= ord($raw[$i++]) << 16;
				$vLen |= ord($raw[$i++]) << 8;
				$vLen |= ord($raw[$i++]);
			}

			$name = substr($raw, $i, $nLen);
			$i += $nLen;

			$value = substr($raw, $i, $vLen);
			$i += $vLen;

			$variables[$name] = $value;
		}

		return $variables;
	}


	/**
	 * @param  string
	 * @return stdClass(appStatus, protocolStatus)
	 * @see http://www.fastcgi.com/devkit/doc/fcgi-spec.html#S5.5
	 */
	private function decodeEndRequestBody($raw)
	{
		return (object) unpack('NappStatus/CprotocolStatus', $raw);
	}


	/** Socket control ********************************************************/
	/**
	 * @param  bool
	 */
	private function socketOpen()
	{
		if ($this->socket !== NULL) {
			throw new FastCGISocketException('Socket already opened.');
		}

		$socket = @fsockopen(
			$this->port === NULL ? "unix://$this->domain" : $this->domain,
			$this->port === NULL ? -1 : $this->port,
			$errorCode,
			$error
		);

		if ($socket === FALSE) {
			throw new FastCGISocketException($error, $errorCode);
		}

		stream_set_blocking($socket, 0);
		$this->socket = $socket;
	}


	/**
	 * @param  int  requested lenght
	 * @return string
	 * @throws FastCGISocketException
	 * @throws FastCGISocketShortReadException
	 */
	private function socketRead($length)
	{
		if ($length < 1) {
			return '';
		}

		$tmp = @fread($this->socket, $length - $dataLen);
		if ($tmp === FALSE) {
			$error = error_get_last();
			throw new FastCGISocketException($error['message']);
		}
		$this->buffer .= $tmp;

		if (($bufferLen = strlen($this->buffer)) < $length) {
			throw new FastCGISocketShortReadException("Reading of $length bytes failed, only $bufferLen read.");
		}

		$ret = substr($this->buffer, 0, $length);
		$this->buffer = substr($this->buffer, $length);

		return $ret;
	}


	/**
	 * @param  string
	 * @return void
	 * @throws SocketException
	 */
	private function socketWrite($str)
	{
		$len = @fwrite($this->socket, $str);
		if ($len === FALSE) {
			$error = error_get_last();
			throw new FastCGISocketException($error['message']);

		} elseif ($len !== strlen($str)) {
			throw new FastCGISocketException("Written " . strlen($str) . " bytes only, $length needed");
		}
	}

}


class FastCGIException extends \RuntimeException
{
}


class FastCGISocketException extends FastCGIException
{
}


class FastCGISocketShortReadException extends FastCGISocketException
{
}
