<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

// regexp
Assert::match('#A#', 'A');
Assert::match('~A~', 'A');
Assert::match('#A#imsxUu', 'A');

Assert::match("#\r\n#", "\r\n");
Assert::match('#\r\n#', "\r\n");

// mask
Assert::match('/A/', '/A/');
Assert::match('(A)', '(A)');
Assert::match('@A@', '@A@');
Assert::match('#A~', '#A~');
Assert::match('#A#imsxUub', '#A#imsxUub');
