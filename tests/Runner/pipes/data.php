<?php declare(strict_types=1);

function pipeData(int $stream, int $offset, int $count): string
{
	$res = '';
	$skip = $offset % 8;
	for ($i = intdiv($offset, 8); strlen($res) < $count + $skip; $i++) {
		$res .= pack('CCnN', $stream ? 0x1A : 0xFF, 0, ($i * 40503) & 0xFFFF, $i); // incl. NUL, CR, LF, Ctrl+Z
	}
	return substr($res, $skip, $count);
}
