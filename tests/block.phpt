<?php

function out($data) {
	file_put_contents('php://stdout', $data);
}

function err($data) {
	file_put_contents('php://stderr', $data);
}

out(str_repeat('o', 5030));
err(str_repeat('e', 5030));
out(str_repeat('O', 5030));
err(str_repeat('E', 5030));
