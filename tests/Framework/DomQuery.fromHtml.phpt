<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\DomQuery;

require __DIR__ . '/../bootstrap.php';

$q = DomQuery::fromHtml('hello');
Assert::true($q->has('body'));
Assert::false($q->has('p'));


$q = DomQuery::fromHtml('<track data=val><footer data-abc=val>hello</footer>');
Assert::true($q->has('footer'));
Assert::true($q->has('footer[data-abc]'));
Assert::false($q->has('footer[id]'));

Assert::true($q->has('track'));
Assert::true($q->has('track[data]'));
Assert::false($q->has('track[id]'));
Assert::false($q->has('track footer'));


$q = DomQuery::fromHtml('<audio controls><source src="horse.mp3" type="audio/mpeg"></audio>');
Assert::true($q->has('source'));

Assert::count(1, $q->find('audio'));
Assert::count(1, $q->find('audio')[0]->find('source'));
Assert::count(0, $q->find('audio')[0]->find('audio'));


$q = DomQuery::fromHtml("<script>\nvar s = '</div>';</script> <br>  <script type='text/javascript'>var s = '</div>';\n</script>");
Assert::true($q->has('script'));
Assert::true($q->has('br'));
Assert::true($q->has('script[type]'));

$q = @DomQuery::fromHtml('<custom-element></custom-element>');
Assert::true($q->has('custom-element'));
