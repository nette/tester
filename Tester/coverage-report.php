<?php

/**
 * Code coverage HTML report generator.
 *
 * This file is part of the Nette Tester.
 */


require_once __DIR__ . '/CodeCoverage/ReportGenerator.php';


$file = __DIR__ . '/coverage.dat';
$root = realpath(__DIR__ . '/../../Nette') . DIRECTORY_SEPARATOR;

$converter = new ReportGenerator($file, $root);
$converter->render();
