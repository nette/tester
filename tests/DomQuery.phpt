<?php

require __DIR__ . '/bootstrap.php';

$q = DomQuery::fromHtml('hello');
Assert::true( $q->has('body') );
Assert::false( $q->has('p') );
