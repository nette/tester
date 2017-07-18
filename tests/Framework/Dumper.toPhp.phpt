<?php

use Tester\Assert;
use Tester\Dumper;

require __DIR__ . '/../bootstrap.php';


class Test
{
	public $x = [10, null];
	private $y = 'hello';
	protected $z = 30.0;
}


Assert::match('null', Dumper::toPhp(null));
Assert::match('true', Dumper::toPhp(true));
Assert::match('false', Dumper::toPhp(false));
Assert::match('0', Dumper::toPhp(0));
Assert::match('1', Dumper::toPhp(1));
Assert::match('0.0', Dumper::toPhp(0.0));
Assert::match('0.1', Dumper::toPhp(0.1));
Assert::match("''", Dumper::toPhp(''));
Assert::match("' '", Dumper::toPhp(' '));
Assert::match("'0'", Dumper::toPhp('0'));
Assert::match('"\\x00"', Dumper::toPhp("\x00"));
Assert::match("'	'", Dumper::toPhp("\t"));
Assert::match('"\\xff"', Dumper::toPhp("\xFF"));
Assert::match('"multi\nline"', Dumper::toPhp("multi\nline"));
Assert::match("'Iñtërnâtiônàlizætiøn'", Dumper::toPhp("I\xc3\xb1t\xc3\xabrn\xc3\xa2ti\xc3\xb4n\xc3\xa0liz\xc3\xa6ti\xc3\xb8n"));
Assert::match('[
	1,
	\'hello\',
	"\r" => [],
	[1, 2],
	[1 => 1, 2, 3, 4, 5, 6, 7, \'abcdefgh\'],
]', Dumper::toPhp([1, 'hello', "\r" => [], [1, 2], [1 => 1, 2, 3, 4, 5, 6, 7, 'abcdefgh']]));

Assert::match('/* resource stream */', Dumper::toPhp(fopen(__FILE__, 'r')));
Assert::match('(object) /* #%a% */ []', Dumper::toPhp((object) null));
Assert::match("(object) /* #%a% */ [
	'a' => 'b',
]", Dumper::toPhp((object) ['a' => 'b']));

Assert::match("Test::__set_state(/* #%a% */ [
	'x' => [10, null],
	'y' => 'hello',
	'z' => 30.0,
])", Dumper::toPhp(new Test));

Assert::match('/* Closure defined in file %a% on line %d% */', Dumper::toPhp(function () {}));
