<?php

/**
 * @internal
 */

$iniFiles = array_merge(
	($tmp = php_ini_loaded_file()) === FALSE ? array() : array($tmp),
	(function_exists('php_ini_scanned_files') && strlen($tmp = php_ini_scanned_files())) ? explode(",\n", trim($tmp)) : array()
);

$extensions = get_loaded_extensions();
natcasesort($extensions);

$isHhvm = defined('HHVM_VERSION');

$values = array(
	'PHP binary' => defined('PHP_BINARY') ? PHP_BINARY : '(not available)',

	'PHP version' . ($isHhvm ? '; HHVM version' : '') => PHP_VERSION . ' (' . PHP_SAPI . ')' . ($isHhvm ? '; ' . HHVM_VERSION : ''),

	'Loaded php.ini files' => count($iniFiles) ? implode(', ', $iniFiles) : ($isHhvm ? '(unable to detect under HHVM)' : '(none)'),

	$last = 'Loaded extensions' => count($extensions) ? implode(', ', $extensions) : '(none)',
);

foreach ($values as $title => $value) {
	echo "\033[1;32m$title\033[0m\n";
	echo "\033[1;37m" . str_repeat('-', strlen($title)) . "\033[0m\n";
	echo $value . "\n";;
	echo $title === $last ? '' : "\n\n";
}
