<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Dumper;

require __DIR__ . '/../bootstrap.php';


Assert::match('NULL', Dumper::toLine(null));
Assert::match('TRUE', Dumper::toLine(true));
Assert::match('FALSE', Dumper::toLine(false));
Assert::match('0', Dumper::toLine(0));
Assert::match('1', Dumper::toLine(1));
Assert::match('0.0', Dumper::toLine(0.0));
Assert::match('0.1', Dumper::toLine(0.1));
Assert::match('INF', Dumper::toLine(INF));
Assert::match('-INF', Dumper::toLine(-INF));
Assert::match('NAN', Dumper::toLine(NAN));
Assert::match("''", Dumper::toLine(''));
Assert::match("' '", Dumper::toLine(' '));
Assert::match("'0'", Dumper::toLine('0'));
Assert::match("'\e[22m\\x00\e[1m'", Dumper::toLine("\x00"));
Assert::match("'\e[22m\\u{FEFF}\e[1m'", Dumper::toLine("\xEF\xBB\xBF")); // BOM
Assert::match("'\e[22m\\t\t\e[1m'", Dumper::toLine("\t"));
Assert::match("'\e[22m\\xFF\e[1m'", Dumper::toLine("\xFF"));
Assert::match("'multi\e[22m\\n\n\e[1mline'", Dumper::toLine("multi\nline"));
Assert::match("'Iñtërnâtiônàlizætiøn'", Dumper::toLine("I\xc3\xb1t\xc3\xabrn\xc3\xa2ti\xc3\xb4n\xc3\xa0liz\xc3\xa6ti\xc3\xb8n"));
Assert::match('resource(stream)', Dumper::toLine(fopen(__FILE__, 'r')));
Assert::match('stdClass(#%a%)', Dumper::toLine((object) [1, 2]));
Assert::match('DateTime(2014-02-13 12:34:56 +0300)(#%a%)', Dumper::toLine(new DateTime('2014-02-13 12:34:56 +0300')));
Assert::match('DateTimeImmutable(2014-02-13 12:34:56 +0300)(#%a%)', Dumper::toLine(new DateTimeImmutable('2014-02-13 12:34:56 +0300')));

Assert::match('[]', Dumper::toLine([]));
Assert::match("[1, 2, 3, 4, 'x']", Dumper::toLine([1, 2, 3, 4, 'x']));
Assert::match('[1 => 1, 2, 3]', Dumper::toLine([1 => 1, 2, 3]));
Assert::match("['a' => [...]]", Dumper::toLine(['a' => [1, 2]]));
Assert::match("['one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', ...]", Dumper::toLine(['one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve']));
