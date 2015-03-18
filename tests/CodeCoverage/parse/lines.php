<?php

class C
{

	function fWhitespace()
	{

	}

	function fSingle()
	{'

     ';}

	function fDouble()
	{"

     ";}

	function fHeredoc()
	{<<<HERE

HERE;
}

	function fNowdoc()
	{<<<'NOW'

NOW;
}

	function fComment()
	{/*

	*/}

	function fDoc()
	{/**

	*/}

}
