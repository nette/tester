<?php

namespace CloverXMLGeneratorTest;

/*
 * The numbers in comments are values reported by xdebug_get_code_coverage().
 */
class Logger
{
	/** @var string */
	private $file;


	/**
	 * @param  string
	 */
	public function __construct($file)
	{
		$this->file = $file;  # 1
	}  # 1


	/** @param  string */
	public function log($message)
	{
		return;  # 1
	}  # -2


	private function purge()
	{
		$notRunCode = 'HERE';  # -1
	}  # -1

}

$logger = new Logger('php://stdout');  # 1
$logger->log('foo');  # 1
