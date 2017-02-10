<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner;


/**
 * Carries information about a single test instance.
 */
class TestInstance
{

	/** @var Job|NULL */
	private $job;

	/** @var string */
	private $fileName;

	/** @var string */
	private $testName;

	/** @var array */
	private $args = [];

	/** @var int */
	private $result;

	/** @var string|NULL */
	private $message;

	/** @var float */
	private $time;


	private function __construct()
	{
	}


	/**
	 * @param Job $job
	 * @param string $testName
	 * @return TestInstance
	 */
	public static function withJob(Job $job, $testName)
	{
		$instance = new self();
		$instance->job = $job;
		$instance->fileName = $job->getFile();
		$instance->testName = $testName;
		$instance->args = $job->getArguments();
		return $instance;
	}


	/**
	 * @param string $fileName
	 * @param string $testName
	 * @param int $result
	 * @param string|NULL $message
	 * @return TestInstance
	 */
	public static function withResult($fileName, $testName, $result, $message = NULL)
	{
		$instance = new self();
		$instance->fileName = $fileName;
		$instance->testName = $testName;
		$instance->result = $result;
		$instance->message = $message;
		return $instance;
	}


	/**
	 * @param float $time
	 * @param int $result
	 * @param string|NULL $message
	 * @return TestInstance
	 */
	public function setResult($time, $result, $message = NULL)
	{
		if ($this->result !== NULL) {
			throw new \LogicException('Cannot overwrite results of a TestInstance that has already finished.');
		}

		$this->time = $time;
		$this->result = $result;
		$this->message = $message;
		return $this;
	}


	/**
	 * @return Job|NULL
	 */
	public function getJob()
	{
		return $this->job;
	}


	/**
	 * @return string
	 */
	public function getFileName()
	{
		return $this->fileName;
	}


	/**
	 * @return string
	 */
	public function getTestName()
	{
		return $this->testName;
	}


	/**
	 * @return string|NULL
	 */
	public function getMethodName()
	{
		return isset($this->args['method']) ? $this->args['method'] : NULL;
	}


	/**
	 * @return string|NULL
	 */
	public function getInstanceName()
	{
		$instanceNameParts = [];
		if (isset($this->args['method'])) {
			$instanceNameParts[] = $this->args['method'];
		}

		if (isset($this->args['multiple'])) {
			$instanceNameParts[] = '#' . $this->args['multiple'];
		}

		if (isset($this->args['dataprovider'])) {
			$instanceNameParts[] = '(' . $this->args['dataprovider'] . ')';
		}

		return implode(' ', $instanceNameParts);
	}


	/**
	 * @return int
	 */
	public function getResult()
	{
		return $this->result;
	}


	/**
	 * @return string
	 */
	public function getMessage()
	{
		return $this->message;
	}


	/**
	 * @return float
	 */
	public function getTime()
	{
		return $this->time;
	}

}
