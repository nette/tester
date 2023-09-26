<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Tester\Environment::bypassFinals();

Assert::error(
	fn() => chmod('unknown', 0777),
	E_WARNING,
);

Assert::error(
	fn() => copy('unknown', 'unknown2'),
	[E_WARNING, E_WARNING, E_WARNING],
);

Assert::false(file_exists('unknown'));

Assert::error(
	fn() => file_get_contents('unknown'),
	[E_WARNING, E_WARNING],
);

Assert::error(
	fn() => file_put_contents(__DIR__, 'content'),
	[E_WARNING, E_WARNING],
);

Assert::error(
	fn() => file('unknown'),
	[E_WARNING, E_WARNING],
);

Assert::error(
	fn() => fileatime('unknown'),
	[E_WARNING, E_WARNING],
);

Assert::error(
	fn() => filectime('unknown'),
	[E_WARNING, E_WARNING],
);

Assert::error(
	fn() => filegroup('unknown'),
	[E_WARNING, E_WARNING],
);

Assert::error(
	fn() => fileinode('unknown'),
	[E_WARNING, E_WARNING],
);

Assert::error(
	fn() => filemtime('unknown'),
	[E_WARNING, E_WARNING],
);

Assert::error(
	fn() => fileowner('unknown'),
	[E_WARNING, E_WARNING],
);

Assert::error(
	fn() => fileperms('unknown'),
	[E_WARNING, E_WARNING],
);

Assert::error(
	fn() => filesize('unknown'),
	[E_WARNING, E_WARNING],
);

Assert::error(
	fn() => filetype('unknown'),
	[E_WARNING, E_WARNING],
);

Assert::error(
	fn() => fopen('unknown', 'r'),
	[E_WARNING, E_WARNING],
);

Assert::same([], glob('unknown'));
Assert::false(is_dir('unknown'));
Assert::false(is_executable('unknown'));
Assert::false(is_file('unknown'));
Assert::false(is_link('unknown'));
Assert::false(is_readable('unknown'));
Assert::false(is_writable('unknown'));

if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
	Assert::error(
		fn() => chgrp('unknown', 'group'),
		E_WARNING,
	);

	Assert::error(
		fn() => chown('unknown', 'user'),
		E_WARNING,
	);

	Assert::error(
		fn() => lchgrp('unknown', 'group'),
		E_WARNING,
	);

	Assert::error(
		fn() => lchown('unknown', 'user'),
		E_WARNING,
	);
}

Assert::error(
	fn() => link('unknown', 'unknown2'),
	E_WARNING,
);

Assert::error(
	fn() => linkinfo('unknown'),
	E_WARNING,
);

Assert::error(
	fn() => lstat('unknown'),
	[E_WARNING, E_WARNING],
);

Assert::error(
	fn() => mkdir(__DIR__),
	E_WARNING,
);

Assert::error(
	fn() => parse_ini_file('unknown'),
	[E_WARNING, E_WARNING],
);

Assert::error(
	fn() => readfile('unknown'),
	[E_WARNING, E_WARNING],
);

Assert::error(
	fn() => readlink('unknown'),
	E_WARNING,
);

Assert::false(realpath('unknown'));

Assert::error(
	fn() => rename('unknown', 'unknown2'),
	E_WARNING,
);

Assert::error(
	fn() => rmdir('unknown'),
	E_WARNING,
);

Assert::error(
	fn() => stat('unknown'),
	[E_WARNING, E_WARNING],
);

Assert::error(
	fn() => unlink('unknown'),
	E_WARNING,
);

Assert::same(-1, fseek(fopen(__FILE__, 'r'), -1));

// not tested: symlink(), touch()
