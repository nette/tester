<?php

function foo() {
	function () use (& $foo) {
	};
}

class C
{
	function fun()
	{
		function () use (& $foo) {
		};
	}
}
