<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Helpers;

require __DIR__ . '/../bootstrap.php';


// the guard is tested on its own, purge() itself is never called with a root path
$isRootPath = fn(string $path): bool => Assert::with(Helpers::class, fn() => self::isRootPath($path));


test('root paths are refused', function () use ($isRootPath) {
	foreach (['', '/', '\\', 'C:', 'C:\\', 'C:/'] as $path) {
		Assert::true($isRootPath($path), $path);
	}

	Assert::true($isRootPath(__DIR__ . str_repeat(DIRECTORY_SEPARATOR . '..', 50)));
});


test('other paths are allowed', function () use ($isRootPath) {
	Assert::false($isRootPath(__DIR__));
	Assert::false($isRootPath(__DIR__ . DIRECTORY_SEPARATOR . '..'));
	Assert::false($isRootPath(__DIR__ . DIRECTORY_SEPARATOR . 'non-existent'));
});


test('purge empties a directory', function () {
	$dir = __DIR__ . '/output/purge';
	@mkdir(__DIR__ . '/output'); // @ - directory may already exist
	@mkdir($dir); // @ - directory may already exist
	@mkdir("$dir/sub"); // @ - directory may already exist
	file_put_contents("$dir/sub/file", '');

	Helpers::purge($dir);
	Assert::same([], glob("$dir/*"));
});
