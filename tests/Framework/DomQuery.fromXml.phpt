<?php

use Tester\Assert,
	Tester\DomQuery;

require __DIR__ . '/../bootstrap.php';

$q = DomQuery::fromXml('<xml><body>hello</body></xml>');
Assert::true( $q->has('body') );
Assert::false( $q->has('p') );
