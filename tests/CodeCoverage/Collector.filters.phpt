<?php

use Tester\Assert;
use Tester\CodeCoverage;

require __DIR__ . '/../bootstrap.php';


if (!extension_loaded('xdebug')) {
	Tester\Environment::skip('Requires Xdebug extension.');
}

class CoveredClass
{

	public function a() {}

	public function b() {}
}

class MockCollector extends CodeCoverage\Collector
{
	public static $annotations;


	public static function start($file)
	{
		self::$file = fopen($file, 'a+');
		xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
	}


	protected static function getCoverAnnotations()
	{
		return self::$annotations;
	}

}

function getCoverage(array $annotations, $tempFile) {
	MockCollector::$annotations = $annotations;
	MockCollector::start($tempFile);

	$instance = new CoveredClass();
	$instance->a();
	$instance->b();

	MockCollector::save();
	$coverage = unserialize(file_get_contents($tempFile));
	if (!$coverage) {
		$coverage = array();
	}

	// return only coverage of CoveredClass to make tests more readable
	$ref = new ReflectionClass('CoveredClass');
	return array_filter($coverage[__FILE__], function($line) use ($ref) {
		return $line >= $ref->getStartLine() && $line <= $ref->getEndLine();
	}, ARRAY_FILTER_USE_KEY);
}

function assertCoverage($exp, array $annotations, $tempFile) {
	Assert::same($exp, getCoverage($annotations, $tempFile));
}

$tempFile = tempnam(sys_get_temp_dir(), 'nette-tester-coverage-');

$a = 16;
$b = 18;

assertCoverage(array($a => -1, $b => -1), array(
	'coversNothing' => TRUE
), $tempFile);

assertCoverage(array($a => 1, $b => -1), array(
	'covers' => 'CoveredClass::a'
), $tempFile);

assertCoverage(array($a => 1, $b => -1), array(
	'covers' => 'CoveredClass::a()'
), $tempFile);

assertCoverage(array($a => 1, $b => 1), array(
	'covers' => array('CoveredClass::a', 'CoveredClass::b')
), $tempFile);

assertCoverage(array($a => 1, $b => 1), array(
	'covers' => array('CoveredClass')
), $tempFile);

Assert::throws(function() {
	MockCollector::$annotations = array('covers' => 'BogusClassName');
	MockCollector::save();
}, 'Exception', "~Failed to find 'BogusClassName'~");

Assert::throws(function () {
	MockCollector::$annotations = array('covers' => 'BogusClassName', 'coversNothing' => TRUE);
	MockCollector::save();
}, 'Exception', "~both @covers and @coversNothing~");
