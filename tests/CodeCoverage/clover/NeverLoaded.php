<?php

throw new \Exception('This file must not be loaded.');


/**
 * This file is never loaded, so Xdebug never reports it in collected coverage data.
 */
class NeverLoaded
{
	public function f()
	{
		echo 'FOO';
		echo 'BAR';
	}
}
