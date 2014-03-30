<?php

/**
 * @internal
 */

$iniFiles = array_merge(
	($tmp = php_ini_loaded_file()) === FALSE ? array() : array($tmp),
	strlen($tmp = php_ini_scanned_files()) ? explode(",\n", trim($tmp)) : array()
);

$extensions = get_loaded_extensions();
natcasesort($extensions);

$values = array(
	'PHP binary' => defined('PHP_BINARY') ? PHP_BINARY : '(not available)',

	'PHP version' => PHP_VERSION . ' (' . PHP_SAPI . ')',

	'Loaded php.ini files' => count($iniFiles) ? implode(', ', $iniFiles) : '(none)',

	$last = 'Loaded extensions' => count($extensions) ? implode(', ', $extensions) : '(none)',
);

foreach ($values as $title => $value) {
	echo "\033[1;32m$title\033[0m\n";
	echo "\033[1;37m" . str_repeat('-', strlen($title)) . "\033[0m\n";
	echo $value . "\n";;
	echo $title === $last ? '' : "\n\n";
}
