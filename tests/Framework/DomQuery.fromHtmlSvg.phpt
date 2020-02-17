<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\DomQuery;

require __DIR__ . '/../bootstrap.php';

$html = <<<'SRC'
<html>
	<body>hello</body>
	<svg version="1.1" baseProfile="full" width="300" height="200" xmlns="http://www.w3.org/2000/svg">
	    <rect width="100%" height="100%" fill="red" />
		<circle cx="150" cy="100" r="80" fill="green" />
		<text x="150" y="125" font-size="60" text-anchor="middle" fill="white">SVG</text>
	</svg>
	<svg role="img" aria-label="" title="" class="shape shape-share-fb view-head button-icon">
		<use xlink:href="#shape-share-fb"></use>
	</svg>
</html>
SRC;

$q = DomQuery::fromHtml($html);
Assert::true($q->has('body'));
Assert::true($q->has('svg'));
Assert::true($q->has('rect'));
Assert::true($q->has('text'));
Assert::true($q->has('circle'));
Assert::true($q->has('use'));
Assert::false($q->has('p'));
