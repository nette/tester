<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\CodeCoverage\Generators;

use DOMDocument;
use DOMElement;
use Tester\CodeCoverage\PhpParser;


class CloverXMLGenerator extends AbstractGenerator
{

	private static $metricAttributesMap = array(
		'packageCount' => 'packages',
		'fileCount' => 'files',
		'linesOfCode' => 'loc',
		'linesOfNonCommentedCode' => 'ncloc',
		'classCount' => 'classes',
		'methodCount' => 'methods',
		'coveredMethodCount' => 'coveredmethods',
		'statementCount' => 'statements',
		'coveredStatementCount' => 'coveredstatements',
		'elementCount' => 'elements',
		'coveredElementCount' => 'coveredelements',
		'conditionalCount' => 'conditionals',
		'coveredConditionalCount' => 'coveredconditionals',
	);


	public function __construct($file, $source = NULL)
	{
		if (!extension_loaded('dom')) {
			throw new \LogicException('CloverXML generator requires DOM extension to be loaded.');
		}
		parent::__construct($file, $source);
	}


	protected function renderSelf()
	{
		$time = time();
		$parser = new PhpParser;

		$doc = new DOMDocument;
		$doc->formatOutput = TRUE;

		$elCoverage = $doc->appendChild($doc->createElement('coverage'));
		$elCoverage->setAttribute('generated', $time);

		// TODO: @name
		$elProject = $elCoverage->appendChild($doc->createElement('project'));
		$elProject->setAttribute('timestamp', $time);
		$elProjectMetrics = $elProject->appendChild($doc->createElement('metrics'));

		$projectMetrics = (object) array(
			'packageCount' => 0,
			'fileCount' => 0,
			'linesOfCode' => 0,
			'linesOfNonCommentedCode' => 0,
			'classCount' => 0,
			'methodCount' => 0,
			'coveredMethodCount' => 0,
			'statementCount' => 0,
			'coveredStatementCount' => 0,
			'elementCount' => 0,
			'coveredElementCount' => 0,
			'conditionalCount' => 0,
			'coveredConditionalCount' => 0,
		);

		foreach ($this->getSourceIterator() as $file) {
			$file = (string) $file;

			$projectMetrics->fileCount++;

			$coverageData = isset($this->data[$file]) ? $this->data[$file] : NULL;

			// TODO: split to <package> by namespace?
			$elFile = $elProject->appendChild($doc->createElement('file'));
			$elFile->setAttribute('name', $file);
			$elFileMetrics = $elFile->appendChild($doc->createElement('metrics'));

			$code = $parser->parse(file_get_contents($file));

			$fileMetrics = (object) array(
				'linesOfCode' => $code->linesOfCode,
				'linesOfNonCommentedCode' => $code->linesOfCode - $code->linesOfComments,
				'classCount' => count($code->classes) + count($code->traits),
				'methodCount' => 0,
				'coveredMethodCount' => 0,
				'statementCount' => 0,
				'coveredStatementCount' => 0,
				'elementCount' => 0,
				'coveredElementCount' => 0,
				'conditionalCount' => 0,
				'coveredConditionalCount' => 0,
			);

			foreach (array_merge($code->classes, $code->traits) as $name => $info) { // TODO: interfaces?
				$elClass = $elFile->appendChild($doc->createElement('class'));
				if (($tmp = strrpos($name, '\\')) === FALSE) {
					$elClass->setAttribute('name', $name);
				} else {
					$elClass->setAttribute('namespace', substr($name, 0, $tmp));
					$elClass->setAttribute('name', substr($name, $tmp + 1));
				}

				$elClassMetrics = $elClass->appendChild($doc->createElement('metrics'));
				$classMetrics = $this->calculateClassMetrics($info, $coverageData);
				self::setMetricAttributes($elClassMetrics, $classMetrics);
				self::appendMetrics($fileMetrics, $classMetrics);
			}
			self::setMetricAttributes($elFileMetrics, $fileMetrics);


			foreach ((array) $coverageData as $line => $count) {
				if ($count === self::CODE_DEAD) {
					continue;
				}

				// Line type can be 'method' but Xdebug does not report such lines as executed.
				$elLine = $elFile->appendChild($doc->createElement('line'));
				$elLine->setAttribute('num', $line);
				$elLine->setAttribute('type', 'stmt');
				$elLine->setAttribute('count', max(0, $count));
			}

			self::appendMetrics($projectMetrics, $fileMetrics);
		}

		// TODO: What about reported (covered) lines outside of class/trait definition?
		self::setMetricAttributes($elProjectMetrics, $projectMetrics);

		echo $doc->saveXML();
	}


	/**
	 * @return \stdClass
	 */
	private function calculateClassMetrics(\stdClass $info, array $coverageData = NULL)
	{
		$stats = (object) array(
			'methodCount' => count($info->methods),
			'coveredMethodCount' => 0,
			'statementCount' => 0,
			'coveredStatementCount' => 0,
			'conditionalCount' => 0,
			'coveredConditionalCount' => 0,
			'elementCount' => NULL,
			'coveredElementCount' => NULL,
		);

		foreach ($info->methods as $name => $methodInfo) {
			list($lineCount, $coveredLineCount) = $this->analyzeMethod($methodInfo, $coverageData);

			$stats->statementCount += $lineCount;

			if ($coverageData !== NULL) {
				$stats->coveredMethodCount += $lineCount === $coveredLineCount ? 1 : 0;
				$stats->coveredStatementCount += $coveredLineCount;
			}
		}

		$stats->elementCount = $stats->methodCount + $stats->statementCount;
		$stats->coveredElementCount = $stats->coveredMethodCount + $stats->coveredStatementCount;

		return $stats;
	}


	/**
	 * @return array
	 */
	private static function analyzeMethod(\stdClass $info, array $coverageData = NULL)
	{
		$count = 0;
		$coveredCount = 0;

		if ($coverageData === NULL) { // Never loaded file
			$count = max(1, $info->end - $info->start - 2);
		} else {
			for ($i = $info->start; $i <= $info->end; $i++) {
				if (isset($coverageData[$i]) && $coverageData[$i] !== self::CODE_DEAD) {
					$count++;
					if ($coverageData[$i] > 0) {
						$coveredCount++;
					}
				}
			}
		}

		return array($count, $coveredCount);
	}


	private static function appendMetrics(\stdClass $summary, \stdClass $add)
	{
		foreach ($add as $name => $value) {
			$summary->{$name} += $value;
		}
	}


	private static function setMetricAttributes(DOMElement $element, \stdClass $metrics)
	{
		foreach ($metrics as $name => $value) {
			$element->setAttribute(self::$metricAttributesMap[$name], $value);
		}
	}

}
