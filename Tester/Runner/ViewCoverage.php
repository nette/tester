<?php

/**
 * coverage.dat HTML viewer.
 *
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (http://davidgrudl.com)
 * Copyright (c) 2009 Jakub Vrana (http://php.vrana.cz)
 *
 * @package    Nette\Test
 */



require_once __DIR__ . '/../Framework/CoverageConverter.php';



$file = __DIR__ . '/coverage.dat';
$root = realpath(__DIR__ . '/../../Nette') . DIRECTORY_SEPARATOR;

$converter = new CoverageConverter($file, $root);
$converter->renderHtml();