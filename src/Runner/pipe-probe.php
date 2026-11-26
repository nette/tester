<?php declare(strict_types=1);

/**
 * Verifies that fstat() tells the number of bytes waiting in a pipe and that reading them does not block.
 * Runs in its own process because a failed assumption would block it; Job kills it then. Exit code 0 means verified.
 * @internal
 */

if (isset($argv[1])) { // writes only when told to
	fgets(STDIN);
	echo str_repeat('a', 1000);
	fgets(STDIN);
	echo str_repeat('b', 500);
	exit(0);
}

$proc = proc_open([PHP_BINARY, '-n', __FILE__, 'writer'], [['pipe', 'r'], ['pipe', 'w'], ['null']], $pipes) ?: exit(1);
[$stdin, $stdout] = $pipes;
$size = fn(): int => fstat($stdout)['size']; // a failure ends the probe with an error
$isRunning = fn(): bool => proc_get_status($proc)['running'];
$check = fn(bool $ok) => $ok || exit(1);

$check($size() === 0); // empty
fwrite($stdin, "\n");
while ($size() !== 1000) {
	$check($isRunning());
	usleep(1000);
}

$check(stream_get_contents($stdout, 400) === str_repeat('a', 400)); // partially read; the rest is in the pipe or in the buffer of PHP
$check($size() + stream_get_meta_data($stdout)['unread_bytes'] === 600);
$check(stream_get_contents($stdout, 600) === str_repeat('a', 600));
$check($size() === 0 && $isRunning()); // emptied
fwrite($stdin, "\n");
while ($isRunning()) {
	usleep(1000);
}

$check($size() === 500); // the writer has ended
$check(stream_get_contents($stdout) === str_repeat('b', 500));
$check($size() === 0 && feof($stdout));
exit(0);
