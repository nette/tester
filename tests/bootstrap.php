<?php

require __DIR__ . '/../Tester/bootstrap.php';


date_default_timezone_set('Europe/Prague');


function test(\Closure $function)
{
	$function();
}
