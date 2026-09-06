<?php declare(strict_types=1);

use Tester\Assert;
use Tester\CodeCoverage\Generators\HtmlGenerator;
use Tester\FileMock;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/CodeCoverage/Generators/AbstractGenerator.php';
require __DIR__ . '/../../src/CodeCoverage/Generators/HtmlGenerator.php';


test('file containing only dead code', function () {
	$source = realpath(__DIR__ . '/fixtures.clover/Logger.php');
	$generator = new HtmlGenerator(FileMock::create(serialize([$source => [1 => -2, 2 => -2]])), [$source]);
	$generator->render($output = FileMock::create('', 'html'));

	Assert::contains('"coverage":100', file_get_contents($output));
});


test('report can be rendered repeatedly in one process', function () {
	$source = realpath(__DIR__ . '/fixtures.clover/Logger.php');
	foreach ([1, 2] as $i) {
		$generator = new HtmlGenerator(FileMock::create(serialize([$source => [19 => 1]])), [$source]);
		$generator->render($output = FileMock::create('', 'html'));
		Assert::contains('Logger.php', file_get_contents($output));
	}
});


test('line count does not depend on a trailing newline', function () {
	@mkdir(__DIR__ . '/output'); // @ - directory may already exist
	$dir = __DIR__ . '/output/line-count';
	@mkdir($dir); // @ - directory may already exist
	file_put_contents("$dir/with-newline.php", "<?php\necho 1;\n");
	file_put_contents("$dir/without-newline.php", "<?php\necho 1;");
	$dir = realpath($dir);

	$generator = new HtmlGenerator(FileMock::create(serialize(["$dir/with-newline.php" => [2 => 1]])), [$dir]);
	$generator->render($output = FileMock::create('', 'html'));

	preg_match('#let _files = (.+);\r?$#m', file_get_contents($output), $m);
	$lineCounts = array_column(json_decode($m[1], associative: true), 'lineCount', 'name');
	ksort($lineCounts);
	Assert::same(['with-newline.php' => 2, 'without-newline.php' => 2], $lineCounts);
});
