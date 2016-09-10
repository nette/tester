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

	/** @var string|NULL */
	private $instanceName;

	/** @var int */
	private $result;

	/** @var string|NULL */
	private $message;


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

		$args = $job->getArguments();
		$instanceNameParts = [];
		if ($args && isset($args['method'])) {
			$instanceNameParts[] = $args['method'];
		}

		if (isset($args['multiple'])) {
			$instanceNameParts[] = '#' . $args['multiple'];
		}

		if (isset($args['dataprovider'])) {
			$instanceNameParts[] = '(' . $args['dataprovider'] . ')';
		}

		$instance->instanceName = implode(' ', $instanceNameParts);

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
	 * @param int $result
	 * @param string|NULL $message
	 * @return TestInstance
	 */
	public function setResult($result, $message = NULL)
	{
		if ($this->result !== NULL) {
			throw new \LogicException('Cannot overwrite results of a TestInstance that has already finished.');
		}

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
	public function getInstanceName()
	{
		return $this->instanceName;
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

}
