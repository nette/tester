<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


Assert::match('#A#', 'A');
Assert::match('~A~', 'A');
Assert::match('#A~', '#A~');
Assert::match('/A/', '/A/');
Assert::match('(A)', '(A)');
Assert::match('@A@', '@A@');

Assert::match('#A#imsxUu', 'A');
Assert::match('#A#imsxUub', '#A#imsxUub');

Assert::match("#\r\n#", "\r\n");
Assert::match('#\r\n#', "\r\n");
