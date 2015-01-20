<?php

use Tester\Assert,
	Tester\DomQuery;

require __DIR__ . '/../bootstrap.php';

$q = DomQuery::fromHtml('hello');
Assert::true( $q->has('body') );
Assert::false( $q->has('p') );


$q = DomQuery::fromHtml('<track data=val><footer data-abc=val>hello</footer>');
Assert::true( $q->has('footer') );
Assert::true( $q->has('footer[data-abc]') );
Assert::false( $q->has('footer[id]') );

Assert::true( $q->has('track') );
Assert::true( $q->has('track[data]') );
Assert::false( $q->has('track[id]') );
Assert::false( $q->has('track footer') );
