<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner\Output;

use Tester\Runner\OutputHandler;
use Tester\Runner\Runner;
use Tester\Runner\TestInstance;


/**
 * TeamCity format output e.g. for PhpStorm plugin.
 */
class TeamCityPrinter implements OutputHandler
{
	/** @var resource */
	private $file;

	/** @var array */
	private $suites = [];


	public function __construct($output = 'php://output')
	{
		$this->file = fopen($output, 'w');
	}


	/**
	 * @param TestInstance[] $testInstances
	 */
	public function begin(array $testInstances)
	{
		foreach ($testInstances as $instance) {
			$fileName = $instance->getFileName();
			if (!isset($this->suites[$fileName])) {
				$this->suites[$fileName] = [
					'cases' => 0,
					'started' => FALSE,
				];
			}

			$this->suites[$fileName]['cases']++;
		}

		fwrite($this->file, "##teamcity[testCount count='" . count($testInstances) . "']\n\n");
	}


	public function result(TestInstance $testInstance)
	{
		$fileName = $testInstance->getFileName();
		$escapedName = $this->escape($testInstance->getTestName());
		$escapedFileName = $this->escape($fileName);
		$flowId = md5($testInstance->getFileName());

		if (!$this->suites[$fileName]['started']) {
			fwrite($this->file, "##teamcity[testSuiteStarted name='$escapedName' locationHint='tester_file://$escapedFileName' flowId='$flowId']\n\n");
			$this->suites[$fileName]['started'] = TRUE;
		}

		$escapedMessage = $this->escape($testInstance->getMessage());
		$locationHint = $testInstance->getMethodName()
			? 'tester_method://' . $fileName . '#' . $testInstance->getMethodName()
			: 'tester_file://' . $fileName;

		$escapedInstanceName = $this->escape($testInstance->getInstanceName());
		fwrite($this->file, "##teamcity[testStarted name='$escapedInstanceName' locationHint='{$this->escape($locationHint)}' flowId='$flowId']\n\n");

		if ($testInstance->getResult() === Runner::SKIPPED) {
			fwrite($this->file, "##teamcity[testIgnored name='$escapedInstanceName' message='$escapedMessage' flowId='$flowId']\n\n");

		} elseif ($testInstance->getResult() === Runner::FAILED) {
			fwrite($this->file, "##teamcity[testFailed name='$escapedInstanceName' message='$escapedMessage' flowId='$flowId']\n\n");
		}

		$time = (int) round($testInstance->getTime() * 1000);
		fwrite($this->file, "##teamcity[testFinished name='$escapedInstanceName' duration='$time' flowId='$flowId']\n\n");

		if (--$this->suites[$fileName]['cases'] < 1) {
			fwrite($this->file, "##teamcity[testSuiteFinished name='$escapedName' flowId='$flowId']\n\n");
		}
	}


	public function end()
	{
	}


	/**
	 * @param string $value
	 * @return string
	 */
	private function escape($value)
	{
		$replace = [
			"|" => "||",
			"'" => "|'",
			"\n" => "|n",
			"\r" => "|r",
			"]" => "|]",
			"[" => "|[",
		];

		return str_replace(array_keys($replace), array_values($replace), $value);
	}

}
