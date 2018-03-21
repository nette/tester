<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\CodeCoverage;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../src/CodeCoverage/PhpParser.php';


$parser = new CodeCoverage\PhpParser;

/*
 * Assertions assume that every line is considered as a line of code.
 */

test(function () use ($parser) {
	$parsed = $parser->parse('<?php ');

	Assert::same(1, $parsed->linesOfCode);
	Assert::same(0, $parsed->linesOfComments);
});


test(function () use ($parser) {
	$parsed = $parser->parse("<?php\n");

	Assert::same(1, $parsed->linesOfCode);
	Assert::same(0, $parsed->linesOfComments);
});


test(function () use ($parser) {
	$parsed = $parser->parse("<?php\n// Comment\n");

	Assert::same(2, $parsed->linesOfCode);
	Assert::same(1, $parsed->linesOfComments);
});


test(function () use ($parser) {
	$parsed = $parser->parse("<?php\n/* Comment */\n");

	Assert::same(2, $parsed->linesOfCode);
	Assert::same(1, $parsed->linesOfComments);
});


test(function () use ($parser) {
	$parsed = $parser->parse("<?php\n/** Doc */\n");

	Assert::same(2, $parsed->linesOfCode);
	Assert::same(1, $parsed->linesOfComments);
});


test(function () use ($parser) {
	$parsed = $parser->parse("<?php\n/* Multi\nline\ncomment */\n");

	Assert::same(4, $parsed->linesOfCode);
	Assert::same(3, $parsed->linesOfComments);
});


test(function () use ($parser) {
	$parsed = $parser->parse("<?php\n/** Multi\n * doc\n * block\n **/\n");

	Assert::same(5, $parsed->linesOfCode);
	Assert::same(4, $parsed->linesOfComments);
});
