<?php

use Tester\Assert,
	Tester\DataProvider;

require __DIR__ . '/bootstrap.php';


Assert::same(
	array(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures/dataprovider.ini', '', FALSE),
	DataProvider::parseAnnotation('fixtures/dataprovider.ini', __FILE__)
);

Assert::same(
	array(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures/dataprovider.query.ini', '= bar', TRUE),
	DataProvider::parseAnnotation('? fixtures/dataprovider.query.ini  = bar', __FILE__)
);
