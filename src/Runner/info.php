<?php

/**
 * @internal
 */

$isPhpDbg = defined('PHPDBG_VERSION');
$isHhvm = defined('HHVM_VERSION');
$extensions = get_loaded_extensions();
natcasesort($extensions);

$info = (object) [
	'binary' => defined('PHP_BINARY') ? PHP_BINARY : NULL,
	'version' => PHP_VERSION,
	'phpDbgVersion' => $isPhpDbg ? PHPDBG_VERSION : NULL,
	'sapi' => PHP_SAPI,
	'hhvmVersion' => $isHhvm ? HHVM_VERSION : NULL,
	'iniFiles' => array_merge(
		($tmp = php_ini_loaded_file()) === FALSE ? [] : [$tmp],
		(function_exists('php_ini_scanned_files') && strlen($tmp = php_ini_scanned_files())) ? explode(",\n", trim($tmp)) : []
	),
	'extensions' => $extensions,
	'canMeasureCodeCoverage' => $isPhpDbg || (!$isHhvm && in_array('xdebug', $extensions, TRUE)),
];

if (isset($_SERVER['argv'][1])) {
	echo serialize($info);
	die();
}

foreach ([
	'PHP binary' => $info->binary ?: '(not available)',
	'PHP version' . ($isPhpDbg ? '; PHPDBG version' : '') . ($isHhvm ? '; HHVM version' : '')
		=> "$info->version ($info->sapi)" . ($isPhpDbg ? "; $info->phpDbgVersion" : '') . ($isHhvm ? "; $info->hhvmVersion" : ''),
	'Loaded php.ini files' => count($info->iniFiles) ? implode(', ', $info->iniFiles) : ($isHhvm ? '(unable to detect under HHVM)' : '(none)'),
	'Loaded extensions' => count($info->extensions) ? implode(', ', $info->extensions) : '(none)',
] as $title => $value) {
	echo "\033[1;32m$title\033[0m:\n$value\n\n";
}

echo "\n";
