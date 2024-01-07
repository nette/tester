<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\DataProvider;

require __DIR__ . '/../bootstrap.php';


Assert::same(
	[__DIR__ . DIRECTORY_SEPARATOR . 'fixtures/dataprovider.ini', '', false],
	DataProvider::parseAnnotation('fixtures/dataprovider.ini', __FILE__),
);

Assert::same(
	[__DIR__ . DIRECTORY_SEPARATOR . 'fixtures/dataprovider.query.ini', '= bar', true],
	DataProvider::parseAnnotation('? fixtures/dataprovider.query.ini  = bar', __FILE__),
);
